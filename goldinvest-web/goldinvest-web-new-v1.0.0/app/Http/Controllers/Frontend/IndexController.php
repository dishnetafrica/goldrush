<?php

namespace App\Http\Controllers\Frontend;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Constants\LanguageConst;
use App\Models\Admin\Language;
use App\Models\Admin\GoldStock;
use App\Models\Admin\UsefulLink;
use App\Models\Admin\AppSettings;
use App\Models\Admin\SiteSections;
use App\Models\Frontend\Subscribe;
use App\Constants\SiteSectionConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\InvestmentPlan;
use App\Models\Frontend\Announcement;
use App\Models\Frontend\ContactRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Providers\Admin\BasicSettingsProvider;

class IndexController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(BasicSettingsProvider $basic_settings)
    {
        $page_title = $basic_settings->get()?->site_name . " | " . $basic_settings->get()?->site_title;
        $lang = selectedLang();
        $default = LanguageConst::NOT_REMOVABLE;
        $banner_slug = Str::slug(SiteSectionConst::BANNER_SECTION);
        $banner = SiteSections::getData($banner_slug)->first();

        $work_slug = Str::slug(SiteSectionConst::HOW_IT_WORK_SECTION);
        $work = SiteSections::getData($work_slug)->first();

        $overview_slug = Str::slug(SiteSectionConst::OVERVIEW_SECTION);
        $overview = SiteSections::getData($overview_slug)->first();

        $why_choose_us_slug = Str::slug(SiteSectionConst::WHY_CHOOSE_US_SECTION);
        $why_choose_us = SiteSections::getData($why_choose_us_slug)->first();

        $app_slug = Str::slug(SiteSectionConst::APP_SECTION);
        $app = SiteSections::getData($app_slug)->first();
        $app_settings = AppSettings::first();

        $testimonial_slug = Str::slug(SiteSectionConst::CLIENT_FEEDBACK_SECTION);
        $testimonial = SiteSections::getData($testimonial_slug)->first();

        $brand_slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $brand = SiteSections::getData($brand_slug)->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();

        $useful_links = UsefulLink::where('status',1)->get();

        return view('frontend.index', compact(
            'page_title',
            'lang',
            'banner',
            'work',
            'overview',
            'why_choose_us',
            'app',
            'app_settings',
            'testimonial',
            'brand',
            'footer',
            'useful_links',
            'default'
        ));
    }
    public function aboutView()
    {
        $page_title = setPageTitle(__("About"));
        $lang = selectedLang();
        $default = LanguageConst::NOT_REMOVABLE;
        $about_slug = Str::slug(SiteSectionConst::ABOUT_US_SECTION);
        $about = SiteSections::getData($about_slug)->first();

        $app_slug = Str::slug(SiteSectionConst::APP_SECTION);
        $app = SiteSections::getData($app_slug)->first();
        $app_settings = AppSettings::first();

        $brand_slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $brand = SiteSections::getData($brand_slug)->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        $useful_links = UsefulLink::where('status',1)->get();
        return view('frontend.pages.about', compact(
            'page_title',
            'about',
            'lang',
            'brand',
            'app',
            'app_settings',
            'footer',
            'useful_links',
            'default'
        ));
    }
    public function contactView()
    {
        $page_title = setPageTitle(__("Contact"));
        $lang = selectedLang();
        $default = LanguageConst::NOT_REMOVABLE;
        $contact_slug = Str::slug(SiteSectionConst::CONTACT_US_SECTION);
        $contact = SiteSections::getData($contact_slug)->first();

        $app_settings = AppSettings::first();

        $brand_slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $brand = SiteSections::getData($brand_slug)->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        $useful_links = UsefulLink::where('status',1)->get();
        return view('frontend.pages.contact', compact(
            'page_title',
            'contact',
            'lang',
            'brand',
            'footer',
            'app_settings',
            'useful_links',
            'default'
        ));
    }
    public function servicesView()
    {
        $page_title = setPageTitle(__("Services"));
        $lang = selectedLang();
        $default = LanguageConst::NOT_REMOVABLE;
        $service_slug = Str::slug(SiteSectionConst::SERVICES_SECTION);
        $service = SiteSections::getData($service_slug)->first();

        $app_slug = Str::slug(SiteSectionConst::APP_SECTION);
        $app = SiteSections::getData($app_slug)->first();
        $app_settings = AppSettings::first();

        $brand_slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $brand = SiteSections::getData($brand_slug)->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        $useful_links = UsefulLink::where('status',1)->get();
        return view('frontend.pages.services', compact(
            'page_title',
            'lang',
            'service',
            'brand',
            'footer',
            'app',
            'app_settings',
            'useful_links',
            'default'
        ));
    }
    public function planView()
    {
        $page_title = setPageTitle(__("Gold Invest"));
        $plans = InvestmentPlan::where('status', 1)->latest('id')->paginate(6);
        $lang = selectedLang();
        $default = LanguageConst::NOT_REMOVABLE;
        $app_slug = Str::slug(SiteSectionConst::APP_SECTION);
        $app = SiteSections::getData($app_slug)->first();
        $app_settings = AppSettings::first();

        $brand_slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $brand = SiteSections::getData($brand_slug)->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        $useful_links = UsefulLink::where('status',1)->get();
        return view('frontend.pages.plan', compact('page_title','lang','plans', 'brand',
        'footer',
        'app',
        'app_settings',
        'useful_links',
        'default'
        ));
    }
    public function goldStore()
    {
        $page_title = setPageTitle(__("Gold Store"));
        $golds = GoldStock::where('status', 1)->latest('id')->paginate(6);
        $lang = selectedLang();
        $default = LanguageConst::NOT_REMOVABLE;
        $app_slug = Str::slug(SiteSectionConst::APP_SECTION);
        $app = SiteSections::getData($app_slug)->first();
        $app_settings = AppSettings::first();

        $brand_slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $brand = SiteSections::getData($brand_slug)->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        $useful_links = UsefulLink::where('status',1)->get();
        return view('frontend.pages.gold-store', compact('page_title','lang','golds','brand','footer','app','app_settings','useful_links','default'));
    }
    public function webJournalView()
    {
        $page_title = setPageTitle(__("Web-Journal"));
        $lang = selectedLang();
        $default = LanguageConst::NOT_REMOVABLE;
        $announcements = Announcement::where('status', GlobalConst::ACTIVE)->paginate(4);
        $latest_announcements = Announcement::active()->orderBy('id','DESC')->limit(3)->get();
        $all_tags = [];
        foreach ($announcements as $key => $item)
         {
                foreach ($item->data->language->$lang->tags as $key => $tag)
                {
                    if (!in_array($tag, $all_tags))
                    {
                    array_push($all_tags, $tag);
                    }
                }
        }
        $app_slug = Str::slug(SiteSectionConst::APP_SECTION);
        $app = SiteSections::getData($app_slug)->first();
        $app_settings = AppSettings::first();

        $brand_slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $brand = SiteSections::getData($brand_slug)->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        $useful_links = UsefulLink::where('status',1)->get();
        return view('frontend.pages.web-journal', compact(
            'page_title',
            'lang',
            'app',
            'app_settings',
            'brand',
            'footer',
            'announcements',
            'latest_announcements',
            'all_tags',
            'useful_links',
            'default'
        ));
    }
    public function journalDetailsView($id, $slug)
    {
        $page_title = setPageTitle(__("Journal Details"));
        $lang = selectedLang();
        $default = LanguageConst::NOT_REMOVABLE;
        $announcement = Announcement::where('id',$id)->where('slug',$slug)->first();
        $latest_announcements = Announcement::active()->where('id',"!=",$id)->latest()->limit(3)->get();
        $announcements = Announcement::where('status', GlobalConst::ACTIVE)->paginate(4);
        $all_tags = [];
        foreach ($announcements as $key => $item)
         {
                foreach ($item->data->language->$lang->tags as $key => $tag)
                {
                    if (!in_array($tag, $all_tags))
                    {
                    array_push($all_tags, $tag);
                    }
                }
        }
        $app_slug = Str::slug(SiteSectionConst::APP_SECTION);
        $app = SiteSections::getData($app_slug)->first();
        $app_settings = AppSettings::first();

        $brand_slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $brand = SiteSections::getData($brand_slug)->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        $useful_links = UsefulLink::where('status',1)->get();
        return view('frontend.pages.journal-detail',compact(
            'page_title',
            'announcement',
            'latest_announcements',
            'lang',
            'app',
            'brand',
            'app_settings',
            'footer',
            'all_tags',
            'useful_links',
            'default'
        ));
    }
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => "required|string|email|max:255|unique:subscribes",
        ]);

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        $validated = $validator->validate();
        try {
            Subscribe::create([
                'email'         => $validated['email'],
                'created_at'    => now(),
            ]);
        } catch (Exception $e) {
            return back()->withErrors($validator)->withInput()->with(['error' => [__('Failed to subscribe. Try again')]]);
        }

        return back()->with(['success' => [__('Subscribed Successfully!')]]);
    }

    public function contactMessageSend(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'name'      => "required|string|max:255",
            'email'     => "required|email|string|max:255",
            'message'   => "required|string|max:5000",
        ])->validate();

        try {
            ContactRequest::create($validated);
        } catch (Exception $e) {
            return back()->with(['error' => [__('Failed to send message. Please Try again')]]);
        }

        return back()->with(['success' => [__('Message send successfully!')]]);
    }

    public function usefulLink($slug)
    {
        $useful_link = UsefulLink::where("slug", $slug)->first();
        if (!$useful_link) abort(404);

        $basic_settings = BasicSettingsProvider::get();

        $app_local = get_default_language_code();
        $lang = selectedLang();
        $default = LanguageConst::NOT_REMOVABLE;
        $page_title = $useful_link->title?->language?->$lang?->title ?? $useful_link->title?->language?->$default?->title ?? $basic_settings->site_name;
        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        $useful_links = UsefulLink::where('status',1)->get();
        return view('frontend.sections.useful-link',compact(
            'page_title',
            'useful_link',
            'footer',
            'lang',
            'useful_links',
            'default'
        ));
    }


    public function languageSwitch(Request $request)
    {
        $code = $request->target;
        $language = Language::where("code", $code)->first();
        if (!$language) {
            return back()->with(['error' => [__('Oops! Language Not Found!')]]);
        }
        Session::put('local', $code);
        Session::put('local_dir', $language->dir);

        return back()->with(['success' => [__('Language Switch to ') . $language->name]]);
    }
}
