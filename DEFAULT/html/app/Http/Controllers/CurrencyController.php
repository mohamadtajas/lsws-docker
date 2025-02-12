<?php

namespace App\Http\Controllers;

use App\Http\Requests\Currency\ChangeRequest;
use App\Http\Requests\Currency\IdRequest;
use App\Http\Requests\Currency\SearchRequest;
use App\Http\Requests\Currency\StoreRequest;
use App\Http\Requests\Currency\UpdateRequest;
use App\Models\Currency;

class CurrencyController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:currency_setup'])->only('currency', 'create', 'edit');
    }
    public function changeCurrency(ChangeRequest $request)
    {
        $currency = Currency::where('code', $request->currency_code)->first();
        $request->session()->put('currency_code', $request->currency_code);
        $request->session()->put('currency_symbol', $currency->symbol);
        $request->session()->put('currency_exchange_rate', $currency->exchange_rate);
        flash(translate('Currency changed to ') . $currency->name)->success();
    }

    public function currency(SearchRequest $request)
    {
        $sort_search = null;
        $currencies = Currency::orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $sort_search = $request->search;
            $currencies = $currencies->where('name', 'like', '%' . $sort_search . '%');
        }
        $currencies = $currencies->paginate(10);

        $active_currencies = Currency::where('status', 1)->get();
        return view('backend.setup_configurations.currencies.index', compact('currencies', 'active_currencies', 'sort_search'));
    }

    public function updateYourCurrency(UpdateRequest $request)
    {
        $currency = Currency::findOrFail($request->id);
        $currency->name = $request->name;
        $currency->symbol = $request->symbol;
        $currency->code = $request->code;
        $currency->exchange_rate = $request->exchange_rate;
        $currency->status = $currency->status;
        if ($currency->save()) {
            flash(translate('Currency updated successfully'))->success();
            return redirect()->route('currency.index');
        } else {
            flash(translate('Something went wrong'))->error();
            return redirect()->route('currency.index');
        }
    }

    public function create()
    {
        return view('backend.setup_configurations.currencies.create');
    }

    public function edit(IdRequest $request)
    {
        $currency = Currency::findOrFail($request->id);
        return view('backend.setup_configurations.currencies.edit', compact('currency'));
    }

    public function store(StoreRequest $request)
    {
        $currency = new Currency;
        $currency->name = $request->name;
        $currency->symbol = $request->symbol;
        $currency->code = $request->code;
        $currency->exchange_rate = $request->exchange_rate;
        $currency->status = '0';
        if ($currency->save()) {
            flash(translate('Currency updated successfully'))->success();
            return redirect()->route('currency.index');
        } else {
            flash(translate('Something went wrong'))->error();
            return redirect()->route('currency.index');
        }
    }

    public function update_status(IdRequest $request)
    {
        $currency = Currency::findOrFail($request->id);
        if ($request->status == 0) {
            if (get_setting('system_default_currency') == $currency->id) {
                return 0;
            }
        }
        $currency->status = $request->status;
        $currency->save();
        return 1;
    }
}
