<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;

class PaymentController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:seller_payment_history'])->only('payment_histories');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function payment_histories()
    {
        $payments = Payment::orderBy('created_at', 'desc')->paginate(15);
        return view('backend.sellers.payment_histories.index', compact('payments'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        $user = User::findOrFail(decrypt($id));
        $payments = Payment::where('seller_id', $user->id)->orderBy('created_at', 'desc')->get();
        if($payments->count() > 0){
            return view('backend.sellers.payment', compact('payments', 'user'));
        }
        flash(translate('No payment history available for this seller'))->warning();
        return back();
    }
}
