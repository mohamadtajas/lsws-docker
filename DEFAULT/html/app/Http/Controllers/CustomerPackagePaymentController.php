<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerPackage\OfflinePaymentApprovalRequest;
use App\Models\CustomerPackagePayment;
use App\Models\CustomerPackage;

class CustomerPackagePaymentController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view_all_offline_customer_package_payments'])->only('offline_payment_request');
    }

    public function offline_payment_request(){
        $package_payment_requests = CustomerPackagePayment::where('offline_payment',1)->orderBy('id', 'desc')->paginate(10);
        return view('manual_payment_methods.customer_package_payment_request', compact('package_payment_requests'));
    }

    public function offline_payment_approval(OfflinePaymentApprovalRequest $request)
    {
        $package_payment    = CustomerPackagePayment::findOrFail($request->id);
        $package_details    = CustomerPackage::findOrFail($package_payment->customer_package_id);

        $package_payment->approval      = $request->status;
        if($package_payment->save()){
            $user                       = $package_payment->user;
            $user->customer_package_id  = $package_payment->customer_package_id;
            $user->remaining_uploads    = $user->remaining_uploads + $package_details->product_upload;
            if($user->save()){
                return 1;
            }
        }
        return 0;
    }

}
