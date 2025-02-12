<?php

namespace App\Http\Controllers;

use App\Http\Requests\SellerWithdraw\MessageModalRequest;
use App\Http\Requests\SellerWithdraw\PaymentModalRequest;
use App\Http\Requests\SellerWithdraw\StoreRequest;
use App\Models\SellerWithdrawRequest;
use App\Models\User;
use Auth;

class SellerWithdrawRequestController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_seller_payout_requests'])->only('index');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $seller_withdraw_requests = SellerWithdrawRequest::latest()->paginate(15);
        return view('backend.sellers.seller_withdraw_requests.index', compact('seller_withdraw_requests'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $seller_withdraw_request = new SellerWithdrawRequest;
        $seller_withdraw_request->user_id = Auth::user()->shop->id;
        $seller_withdraw_request->amount = $request->amount;
        $seller_withdraw_request->message = $request->message;
        $seller_withdraw_request->status = '0';
        $seller_withdraw_request->viewed = '0';
        if ($seller_withdraw_request->save()) {
            flash(translate('Request has been sent successfully'))->success();
            return redirect()->route('withdraw_requests.index');
        } else {
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }

    public function payment_modal(PaymentModalRequest $request)
    {
        $user = User::findOrFail($request->id);
        $seller_withdraw_request = SellerWithdrawRequest::where('id', $request->seller_withdraw_request_id)->first();
        return view('backend.sellers.seller_withdraw_requests.payment_modal', compact('user', 'seller_withdraw_request'));
    }

    public function message_modal(MessageModalRequest $request)
    {
        $seller_withdraw_request = SellerWithdrawRequest::findOrFail($request->id);
        if (Auth::user()->user_type == 'seller') {
            return view('frontend.'.get_setting('homepage_select').'.partials.withdraw_message_modal', compact('seller_withdraw_request'));
        } elseif (Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'staff') {
            return view('backend.sellers.seller_withdraw_requests.withdraw_message_modal', compact('seller_withdraw_request'));
        }
    }
}
