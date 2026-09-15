<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Http\Helpers\Response;
use App\Models\Admin\Language;
use App\Constants\LanguageConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\InvestmentPlan;
use Illuminate\Support\Facades\Validator;

class InvestmentPlanController extends Controller
{
    public function index()
    {
        $page_title = __("Gold Investment Plan");
        $plans = InvestmentPlan::orderByDesc("id")->paginate(10);

        return view('admin.sections.investment-plan.index', compact('page_title', 'plans'));
    }
    public function planCreate()
    {
        $page_title = __("Create New Plan");
        $languages = Language::get();

        return view('admin.sections.investment-plan.create', compact("page_title", "languages"));
    }

    public function planStore(Request $request)
    {

        $basic_field_name = [
            'name' => "required|string|max:255",
            'title' => "nullable|string|max:255",
        ];

        $data['language']  = $this->contentValidate($request, $basic_field_name);

        $validated = Validator::make($request->all(), [
            'plan_duration'  => "required|integer",
            'profit_return_type'    => "required|in:" . GlobalConst::INVEST_PROFIT_DAILY_BASIS . "," . GlobalConst::INVEST_PROFIT_ONE_TIME,
            'minimum_investment' => "required|numeric|gt:0",
            'minimum_investment_offer' => "nullable|numeric|gt:0",
            'maximum_investment' => "required|numeric|gte:" . $request->minimum_investment,
            'profit' => "required|numeric|gt:0",
            'profit_percentage' => "required|numeric|gt:0",
            'image'  => 'required|image|mimes:png,jpg,jpeg,svg,webp',
        ])->validate();
        // make slug
        $not_removable_lang = LanguageConst::NOT_REMOVABLE;
        $slug_text = $data['language'][$not_removable_lang]['name'] ?? "";
        if ($slug_text == "") {
            $slug_text = $data['language'][get_default_language_code()]['name'] ?? "";
            if ($slug_text == "") {
                $slug_text = Str::uuid();
            }
        }
        $slug = Str::slug(Str::lower($slug_text));

        if (InvestmentPlan::where('slug', $slug)->exists()) return back()->with(['error' => [__('Plan name is similar. Please update/change this name')]]);

        if ($request->hasFile('image')) {
            $image = get_files_from_fileholder($request, 'image');
            $upload = upload_files_from_path_dynamic($image, 'site-section');
            $validated['image'] = $upload;
        }

        try {
            InvestmentPlan::create([
                'slug'   => $slug,
                'data'  => $data,
                'plan_duration'   => $validated['plan_duration'],
                'profit_return_type'   => $validated['profit_return_type'],
                'minimum_investment'   => $validated['minimum_investment'],
                'minimum_investment_offer'   => $validated['minimum_investment_offer'],
                'maximum_investment'   => $validated['maximum_investment'],
                'image'   => $validated['image'],
                'profit'   => $validated['profit'],
                'profit_percentage'   => $validated['profit_percentage']
            ]);
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return redirect()->route('admin.investment.plan.index')->with(['success' => [__('Plan created successfully!')]]);
    }

    public function planStatusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status'                    => 'required|boolean',
            'data_target'               => 'required|integer|exists:investment_plans,id',
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error, null, 400);
        }
        $validated = $validator->validate();


        try {
            $plan = InvestmentPlan::find($validated['data_target']);
            if ($plan) {
                $plan->update([
                    'status'    => ($validated['status'] == true) ? false : true,
                ]);
            }
        } catch (Exception $e) {
            $error = ['error' => [__('Something went wrong! Please try again.')]];
            return Response::error($error, null, 500);
        }

        $success = ['success' => [__('Plan status updated successfully!')]];
        return Response::success($success, null, 200);
    }

    public function planDelete(Request $request)
    {
        $request->validate([
            'target'    => "required|integer|exists:investment_plans,id"
        ]);

        try {
            $plan = InvestmentPlan::find($request->target);
            if ($plan) {
                $image_name = $plan->image ?? null;
                if ($image_name) {
                    $image_link = get_files_path('site-section') . "/" . $image_name;
                    delete_file($image_link);
                }
                $plan->delete();
            }
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }
        return back()->with(['success' => [__('Plan deleted successfully!')]]);
    }

    public function planEdit($id)
    {
        $plan = InvestmentPlan::find($id);
        if (!$plan) return back()->with(['error' => [__("Plan doesn't exists!")]]);
        $page_title = __("Plan Edit");
        $languages = Language::get();

        return view('admin.sections.investment-plan.edit', compact("page_title", "plan", "languages"));
    }

    public function planUpdate(Request $request, $id)
    {
        $plan = InvestmentPlan::find($id);
        if (!$plan) return back()->with(['error' => [__("Gold doesn't exists!")]]);

        $basic_field_name = [
            'name'         => "required|string|max:255",
            'title'         => "required|string|max:255",
        ];

        $data['language']  = $this->contentValidate($request, $basic_field_name);

        $validated = Validator::make($request->all(), [
            'plan_duration'  => "required|integer",
            'profit_return_type'    => "required|in:" . GlobalConst::INVEST_PROFIT_DAILY_BASIS . "," . GlobalConst::INVEST_PROFIT_ONE_TIME,
            'minimum_investment' => "required|numeric|gt:0",
            'minimum_investment_offer' => "nullable|numeric|gt:0",
            'maximum_investment' => "required|numeric|gt:0". $request->minimum_investment,
            'profit' => "required|numeric|gt:0",
            'profit_percentage' => "required|numeric|gt:0",
            'image'  => 'nullable|image|mimes:png,jpg,jpeg,svg,webp',
        ])->validate();
        $validated['image'] = $plan->image ?? null;
        if ($request->hasFile('image')) {
            $image = get_files_from_fileholder($request, 'image');
            $upload = upload_files_from_path_dynamic($image, 'site-section');
            $validated['image'] = $upload;
        }

        try {
            $plan->update([
                'data'  => $data,
                'plan_duration'   => $validated['plan_duration'],
                'profit_return_type'   => $validated['profit_return_type'],
                'minimum_investment'   => $validated['minimum_investment'],
                'minimum_investment_offer'   => $validated['minimum_investment_offer'],
                'maximum_investment'   => $validated['maximum_investment'],
                'image'   => $validated['image'],
                'profit'   => $validated['profit'],
                'profit_percentage'   => $validated['profit_percentage']
            ]);
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return redirect()->route('admin.investment.plan.index')->with(['success' => [__('Plan updated successfully!')]]);
    }

    /**
     * Method for validate request data and re-decorate language wise data
     * @param object $request
     * @param array $basic_field_name
     * @return array $language_wise_data
     */
    public function contentValidate($request, $basic_field_name, $modal = null)
    {
        $languages = Language::get();
        $current_local = get_default_language_code();
        $validation_rules = [];
        $language_wise_data = [];
        foreach ($request->all() as $input_name => $input_value) {
            foreach ($languages as $language) {
                $input_name_check = explode("_", $input_name);
                $input_lang_code = array_shift($input_name_check);
                $input_name_check = implode("_", $input_name_check);
                if ($input_lang_code == $language['code']) {
                    if (array_key_exists($input_name_check, $basic_field_name)) {
                        $langCode = $language['code'];
                        if ($current_local == $langCode) {
                            $validation_rules[$input_name] = $basic_field_name[$input_name_check];
                        } else {
                            $validation_rules[$input_name] = str_replace("required", "nullable", $basic_field_name[$input_name_check]);
                        }
                        $language_wise_data[$langCode][$input_name_check] = $input_value;
                    }
                    break;
                }
            }
        }
        if ($modal == null) {
            $validated = Validator::make($request->all(), $validation_rules)->validate();
        } else {
            $validator = Validator::make($request->all(), $validation_rules);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput()->with("modal", $modal);
            }
            $validated = $validator->validate();
        }

        return $language_wise_data;
    }
}
