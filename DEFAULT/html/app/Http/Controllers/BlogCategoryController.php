<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlogCategory\SearchRequest;
use App\Http\Requests\BlogCategory\StoreRequest;
use App\Models\BlogCategory;

class BlogCategoryController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_blog_categories'])->only('index');
        $this->middleware(['permission:add_blog_category'])->only('create');
        $this->middleware(['permission:edit_blog_category'])->only('edit');
        $this->middleware(['permission:delete_blog_category'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(SearchRequest $request)
    {
        $sort_search = null;
        $categories = BlogCategory::orderBy('category_name', 'asc');

        if ($request->has('search')) {
            $sort_search = $request->search;
            $categories = $categories->where('category_name', 'like', '%' . $sort_search . '%');
        }

        $categories = $categories->paginate(15);
        return view('backend.blog_system.category.index', compact('categories', 'sort_search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $all_categories = BlogCategory::all();
        return view('backend.blog_system.category.create', compact('all_categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {

        $request->validate([
            'category_name' => 'required|max:255',
        ]);

        $category = new BlogCategory;

        $category->category_name = $request->category_name;
        $category->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->category_name));

        $category->save();


        flash(translate('Blog category has been created successfully'))->success();
        return redirect()->route('blog-category.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(string $id)
    {
        $cateogry = BlogCategory::findOrFail($id);
        $all_categories = BlogCategory::all();

        return view('backend.blog_system.category.edit',  compact('cateogry', 'all_categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreRequest $request,string $id)
    {
        $category = BlogCategory::findOrFail($id);

        $category->category_name = $request->category_name;
        $category->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->category_name));

        $category->save();


        flash(translate('Blog category has been updated successfully'))->success();
        return redirect()->route('blog-category.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $blogCategory = BlogCategory::findOrFail($id);

        $blogCategory->delete();

        return redirect('admin/blog-category');
    }
}
