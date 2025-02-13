<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tax\ChangeStatusRequest;
use App\Http\Requests\Tax\StoreRequest;
use App\Models\Tax;

class TaxController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:vat_&_tax_setup'])->only('index', 'create', 'edit', 'destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $all_taxes = Tax::orderBy('created_at', 'desc')->get();
        return view('backend.setup_configurations.tax.index', compact('all_taxes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $tax = new Tax;
        $tax->name = $request->name;

        if ($tax->save()) {

            flash(translate('Tax has been inserted successfully'))->success();
            return redirect()->route('tax.index');
        } else {
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(string $id)
    {
        $tax = Tax::findOrFail($id);
        return view('backend.setup_configurations.tax.edit', compact('tax'));
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
        $tax = Tax::findOrFail($id);
        $tax->name = $request->name;
        if ($tax->save()) {
            flash(translate('Tax has been updated successfully'))->success();
            return redirect()->route('tax.index');
        } else {
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }

    public function change_tax_status(ChangeStatusRequest $request)
    {
        $tax = Tax::findOrFail($request->id);
        if ($tax->tax_status == 1) {
            $tax->tax_status = 0;
        } else {
            $tax->tax_status = 1;
        }

        if ($tax->save()) {
            return 1;
        }
        return 0;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        if (Tax::destroy($id)) {
            flash(translate('Tax has been deleted successfully'))->success();
            return redirect()->route('tax.index');
        } else {
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }

}
