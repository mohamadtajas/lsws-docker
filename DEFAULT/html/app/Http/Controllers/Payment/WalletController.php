<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomerPackageController;
use App\Models\CombinedOrder;
use App\Models\CustomerPackage;
use App\Models\SellerPackage;
use App\Models\Wallet;
use Session;
use Auth;
use Google\Service\Monitoring\Custom;

class WalletController extends Controller
{
    public function pay()
    {
        if (Session::has('payment_type')) {
            if (Session::get('payment_type') == 'cart_payment') {
                $user = Auth::user();
                $invite_discount = 0;

                $combined_order = CombinedOrder::findOrFail(Session::get('combined_order_id'));
                if ($user->balance >= $combined_order->grand_total) {
                    if (get_setting('invite_system') == 1 && $user->invitation->count() == 1) {
                        $invitation = $user->invitation->first();
                        $invited_by_user = $invitation->user;

                        $invite_discount = env('INVITE_DISCOUNT');
                        $user->balance += $invite_discount;
                        $user->save();

                        $invite_amount = env('INVITE_AMOUNT');
                        $invited_by_user->balance += $invite_amount;
                        $invited_by_user->save();

                        $wallet = new Wallet;
                        $wallet->user_id = $invited_by_user->id;
                        $wallet->amount = $invite_amount;
                        $wallet->payment_method = 'invite';
                        $wallet->payment_details =  translate('You have received') . ' ' . $invite_amount . ' ' . get_system_default_currency()->code . ' ' . translate('by invitation from') . ' ' . $user->email;
                        $wallet->save();

                        $wallet = new Wallet;
                        $wallet->user_id = Auth::user()->id;
                        $wallet->amount = $invite_discount;
                        $wallet->payment_method = 'invite';
                        $wallet->payment_details =  translate('You have received') . ' ' . $invite_discount . ' ' . get_system_default_currency()->code . ' ' . translate('by invitation from') . ' ' . $invited_by_user->email;
                        $wallet->save();


                        $invitation->used = 1;
                        $invitation->save();
                    }
                    $user->balance -= $combined_order->grand_total;
                    $user->save();
                    return (new CheckoutController)->checkout_done($combined_order->id, null);
                } else {
                    flash(translate('Insufficient balance'))->warning();
                    return redirect()->route('home');
                }
            } elseif (Session::get('payment_type') == 'customer_package_payment') {
                $user = Auth::user();
                $customer_package = CustomerPackage::findOrFail(Session::get('payment_data')['customer_package_id']);
                $amount = $customer_package->amount;
                if ($user->balance >= $amount) {
                    $user->balance -= $amount;
                    $user->save();
                    return (new CustomerPackageController)->purchase_payment_done(Session::get('payment_data'), null);
                } else {
                    flash(translate('Insufficient balance'))->warning();
                    return redirect()->route('home');
                }
            } elseif (Session::get('payment_type') == 'seller_package_payment') {
                $user = Auth::user();
                $seller_package = SellerPackage::findOrFail(Session::get('payment_data')['seller_package_id']);
                $amount = $seller_package->amount;
                if ($user->balance >= $amount) {
                    $user->balance -= $amount;
                    $user->save();
                    return (new CustomerPackageController)->purchase_payment_done(Session::get('payment_data'), null);
                } else {
                    flash(translate('Insufficient balance'))->warning();
                    return redirect()->route('home');
                }
            }
        }
    }
}
