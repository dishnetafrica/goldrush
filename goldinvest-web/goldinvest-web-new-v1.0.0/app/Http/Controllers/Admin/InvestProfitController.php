<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\InvestmentProfitLog;
use App\Http\Controllers\Controller;

class InvestProfitController extends Controller
{
    public function index()
    {
        $page_title = __("Invest Profits");
        $lang = selectedLang();
        $profits = InvestmentProfitLog::has('invest')->orderByDesc("id")->paginate(7);

        return view('admin.sections.invest-profit.index',compact(
            'page_title',
            'profits',
            'lang'
        ));
    }
}
