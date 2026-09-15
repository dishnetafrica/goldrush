<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\Admin\Language;
use App\Models\Admin\GoldStock;
use App\Constants\LanguageConst;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class GoldStockController extends Controller
{
    public function index() {
        $page_title = __("Gold Store");
        $golds = GoldStock::orderByDesc("id")->paginate(10);

        return view('admin.sections.gold-stock.index',compact('page_title','golds'));
    }

    public function goldCreate() {
        $page_title = __("Create New Gold");
        $languages = Language::get();

        return view('admin.sections.gold-stock.create',compact("page_title","languages"));
    }

    public function goldStore(Request $request) {

        $basic_field_name = [
            'title' => "required|string|max:255",
        ];

        $data['language']  = $this->contentValidate($request,$basic_field_name);
        $title = $data;

        $validated = Validator::make($request->all(),[
            'type'  => "required|string",
            'country_of_origin' => "required|string",
            'manufacturer' => "required|string",
            'weight' => "required",
            'purity' => "required",
            'price' => "required|numeric|gt:0",
            'charge' => "required|numeric|gt:0",
            'image'  => 'required|image|mimes:png,jpg,jpeg,svg,webp',
        ])->validate();

        // make slug
        $not_removable_lang = LanguageConst::NOT_REMOVABLE;
        $slug_text = $data['language'][$not_removable_lang]['title'] ?? "";
        if($slug_text == "") {
            $slug_text = $data['language'][get_default_language_code()]['title'] ?? "";
            if($slug_text == "") {
                $slug_text = Str::uuid();
            }
        }
        $slug = Str::slug(Str::lower($slug_text));

        if(GoldStock::where('slug',$slug)->exists()) return back()->with(['error' => [__("Gold title is similar. Please update/change this title")]]);

        if ($request->hasFile('image')) {
            $image = get_files_from_fileholder($request, 'image');
            $upload = upload_files_from_path_dynamic($image, 'site-section');
            $validated['image'] = $upload;
        }

        try{
            GoldStock::create([
                'slug'   => $slug,
                'title'  => $title,
                'type'   => $validated['type'],
                'weight'   => $validated['weight'],
                'purity'   => $validated['purity'],
                'manufacturer'   => $validated['manufacturer'],
                'country_of_origin'   => $validated['country_of_origin'],
                'image'   => $validated['image'],
                'price'   => $validated['price'],
                'charge'   => $validated['charge']
            ]);
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return redirect()->route('admin.gold.stock.index')->with(['success' => [__('Gold created successfully!')]]);
    }

    public function goldStatusUpdate(Request $request) {
        $validator = Validator::make($request->all(), [
            'status'                    => 'required|boolean',
            'input_name'                => 'required|string',
            'data_target'               => 'required|integer|exists:gold_stocks,id',
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error, null, 400);
        }
        $validated = $validator->validate();


        try {
            $gold = GoldStock::find($validated['data_target']);
            if($gold) {
                $gold->update([
                    'status'    => ($validated['status'] == true) ? false : true,
                ]);
            }
        } catch (Exception $e) {
            $error = ['error' => [__('Something went wrong! Please try again.')]];
            return Response::error($error, null, 500);
        }

        $success = ['success' => [__('Gold status updated successfully!')]];
        return Response::success($success, null, 200);
    }

    public function goldDelete(Request $request) {
        $request->validate([
            'target'    => "required|integer|exists:gold_stocks,id"
        ]);

        try{
            $gold = GoldStock::find($request->target);
            if($gold) {
                $image_name = $gold->image ?? null;
                if($image_name) {
                    $image_link = get_files_path('site-section') . "/" . $image_name;
                    delete_file($image_link);
                }
                $gold->delete();
            }
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong. Please try again')]]);
        }
        return back()->with(['success' => [__('Gold deleted successfully!')]]);
    }

    public function goldEdit($id) {
        $gold = GoldStock::find($id);
        if(!$gold) return back()->with(['error' => [__("Gold doesn't exists!")]]);
        $page_title = __("Gold Edit");
        $languages = Language::get();

        return view('admin.sections.gold-stock.edit',compact("page_title","gold","languages"));
    }

    public function goldUpdate(Request $request,$id) {
        $gold = GoldStock::find($id);
        if(!$gold) return back()->with(['error' => [__("Gold doesn't exists!")]]);

        $basic_field_name = [
            'title'         => "required|string|max:255",
        ];

        $data['language']  = $this->contentValidate($request,$basic_field_name);

        $validated = Validator::make($request->all(),[
            'type'  => "required|string",
            'country_of_origin' => "required|string",
            'manufacturer' => "required|string",
            'weight' => "required",
            'purity' => "required",
            'price' => "required|numeric|gt:0",
            'charge' => "required|numeric|gt:0",
            'image'  => 'nullable|image|mimes:png,jpg,jpeg,svg,webp',
        ])->validate();
        $validated['image'] = $gold->image ?? null;
        if ($request->hasFile('image')) {
            $image = get_files_from_fileholder($request, 'image');
            $upload = upload_files_from_path_dynamic($image, 'site-section');
            $validated['image'] = $upload;
        }

        try{
            $gold->update([
                'type'   => $validated['type'],
                'weight'   => $validated['weight'],
                'purity'   => $validated['purity'],
                'manufacturer'   => $validated['manufacturer'],
                'country_of_origin'   => $validated['country_of_origin'],
                'image'   => $validated['image'],
                'title'   => $data,
                'price'   => $validated['price'],
                'charge'   => $validated['charge']
            ]);
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return redirect()->route('admin.gold.stock.index')->with(['success' => [__('Gold updated successfully!')]]);
    }

    /**
     * Method for validate request data and re-decorate language wise data
     * @param object $request
     * @param array $basic_field_name
     * @return array $language_wise_data
     */
    public function contentValidate($request,$basic_field_name,$modal = null) {
        $languages = Language::get();
        $current_local = get_default_language_code();
        $validation_rules = [];
        $language_wise_data = [];
        foreach($request->all() as $input_name => $input_value) {
            foreach($languages as $language) {
                $input_name_check = explode("_",$input_name);
                $input_lang_code = array_shift($input_name_check);
                $input_name_check = implode("_",$input_name_check);
                if($input_lang_code == $language['code']) {
                    if(array_key_exists($input_name_check,$basic_field_name)) {
                        $langCode = $language['code'];
                        if($current_local == $langCode) {
                            $validation_rules[$input_name] = $basic_field_name[$input_name_check];
                        }else {
                            $validation_rules[$input_name] = str_replace("required","nullable",$basic_field_name[$input_name_check]);
                        }
                        $language_wise_data[$langCode][$input_name_check] = $input_value;
                    }
                    break;
                }
            }
        }
        if($modal == null) {
            $validated = Validator::make($request->all(),$validation_rules)->validate();
        }else {
            $validator = Validator::make($request->all(),$validation_rules);
            if($validator->fails()) {
                return back()->withErrors($validator)->withInput()->with("modal",$modal);
            }
            $validated = $validator->validate();
        }

        return $language_wise_data;
    }
}
