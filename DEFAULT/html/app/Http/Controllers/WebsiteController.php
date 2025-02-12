<?php

namespace App\Http\Controllers;

use App\Http\Requests\Website\LangRequest;
use App\Models\Page;

class WebsiteController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:header_setup'])->only('header');
        $this->middleware(['permission:footer_setup'])->only('footer');
        $this->middleware(['permission:view_all_website_pages'])->only('pages');
        $this->middleware(['permission:website_appearance'])->only('appearance');
        $this->middleware(['permission:select_homepage'])->only('select_homepage');
        $this->middleware(['permission:authentication_layout_settings'])->only('authentication_layout_settings');
    }

    public function header()
    {
        return view('backend.website_settings.header');
    }
    public function footer(LangRequest $request)
    {
        $lang = $request->lang;
        return view('backend.website_settings.footer', compact('lang'));
    }
    public function pages()
    {
        $page = Page::where('type', '!=', 'home_page')->get();
        return view('backend.website_settings.pages.index', compact('page'));
    }
    public function appearance()
    {
        return view('backend.website_settings.appearance');
    }
    public function select_homepage()
    {
        return view('backend.website_settings.select_homepage');
    }

    public function authentication_layout_settings()
    {
        return view('backend.website_settings.authentication_layout_settings');
    }
}
