<?php

namespace App\Http\Controllers\User;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\UserSupportChat;
use App\Models\UserSupportTicket;
use App\Http\Controllers\Controller;
use App\Constants\SupportTicketConst;
use Illuminate\Support\Facades\Validator;
use App\Models\UserSupportTicketAttachment;
use App\Events\Admin\SupportConversationEvent;

class SupportTicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("Support Tickets");
        $breadcrumb = __("Support Tickets");
        $support_tickets = UserSupportTicket::authTickets()->orderByDesc("id")->paginate(10);
        return view('user.sections.support-ticket.index', compact('page_title','support_tickets','breadcrumb'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $page_title = __("Add New Ticket");
        $breadcrumb    = __("Add New Ticket");
        return view('user.sections.support-ticket.create', compact('page_title','breadcrumb'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'subject'           => "required|string|max:255",
            'desc'              => "required|string|max:5000",
            'attachment.*'      => "nullable|file|max:204800",
        ]);
        $validated = $validator->validate();
        $validated['token']         = generate_unique_string('user_support_tickets','token',16,'ST');
        $validated['user_id']       = auth()->user()->id;
        $validated['name']          = auth()->user()->full_name ?? null;
        $validated['email']         = auth()->user()->email;
        $validated['status']        = 0;
        $validated['created_at']    = now();
        $validated = Arr::except($validated,['attachment']);

        try{
            $support_ticket_id = UserSupportTicket::insertGetId($validated);
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        if($request->hasFile('attachment')) {
            $validated_files = $request->file("attachment");
            $attachment = [];
            $files_link = [];
            foreach($validated_files as $item) {
                $upload_file = upload_file($item,'support-attachment');
                if($upload_file != false) {
                    $attachment[] = [
                        'user_support_ticket_id'    => $support_ticket_id,
                        'attachment'                => $upload_file['name'],
                        'attachment_info'           => json_encode($upload_file),
                        'created_at'                => now(),
                    ];
                }

                $files_link[] = get_files_path('support-attachment') . "/". $upload_file['name'];
            }

            try{
                UserSupportTicketAttachment::insert($attachment);
            }catch(Exception $e) {
                $support_ticket_id->delete();
                delete_files($files_link);

                return back()->with(['error' => [__('Oops! Failed to upload attachment. Please try again.')]]);
            }
        }

        return redirect()->route('user.support.ticket.index')->with(['success' => [__('Support ticket created successfully!')]]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function conversation($encrypt_id)
    {
        $support_ticket_id = decrypt($encrypt_id);
        $support_ticket = UserSupportTicket::findOrFail($support_ticket_id);
        $breadcrumb = __("Support Chat");
        $page_title = __("Support Chat");
        return view('user.sections.support-ticket.conversation', compact('page_title','support_ticket','breadcrumb'));
    }

    public function messageSend(Request $request) {
        $validator = Validator::make($request->all(),[
            'message'       => 'required|string|max:200',
            'support_token' => 'required|string',
        ]);
        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $validated = $validator->validate();

        $support_ticket = UserSupportTicket::notSolved($validated['support_token'])->first();
        if(!$support_ticket) return Response::error(['error' =>  [__('This support ticket is closed.')]]);

        $data = [
            'user_support_ticket_id'    => $support_ticket->id,
            'sender'                    => auth()->user()->id,
            'sender_type'               => "USER",
            'message'                   => $validated['message'],
            'receiver_type'             => "ADMIN",
        ];

        try{
            $chat_data = UserSupportChat::create($data);
        }catch(Exception $e) {
            return $e;
            $error = ['error' => [__('SMS Sending failed! Please try again.')]];
            return Response::error($error,null,500);
        }

        try{
            event(new SupportConversationEvent($support_ticket,$chat_data));
        }catch(Exception $e) {
            return $e;
            $error = ['error' => [__('SMS Sending failed! Please try again.')]];
            return Response::error($error,null,500);
        }
    }
    public function solve(Request $request) {
        $validator = Validator::make($request->all(),[
            'target'    => 'required|string|exists:user_support_tickets,token',
        ]);
        $validated = $validator->validate();

        $support_ticket = UserSupportTicket::where("token",$validated['target'])->first();
        if($support_ticket->status == SupportTicketConst::SOLVED) return back()->with(['warning' =>  [__('This ticket is already solved!')]]);

        try{
            $support_ticket->update([
                'status'        => SupportTicketConst::SOLVED,
            ]);
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' =>  [__('Success')]]);
    }
}
