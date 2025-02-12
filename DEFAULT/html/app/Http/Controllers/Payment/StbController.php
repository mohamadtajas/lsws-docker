<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerPackageController;
use App\Http\Controllers\SellerPackageController;
use App\Http\Controllers\WalletController;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;
use App\Models\Address;
use App\Models\Cart;
use App\Models\StbCard;
use App\Models\CombinedOrder;
use App\Models\CustomerPackage;
use App\Models\SellerPackage;
use App\Models\Order;
use Session;
use Auth;
use Redirect;

class StbController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
     public function __construct() 
     {
        $this->middleware('auth'); // later enable it when needed user login while payment
    }
    
    public function pay()
    {
         $stbBaseUrl = env('STB_BASE_URL');
         $StbSecretKey =  env('STB_SECRET_KEY');
         
         
         date_default_timezone_set('UTC');
         $expire_time =  date('YmdHi', strtotime(date('YmdHi') . '+1 minute'));
         $token = ['token' => env('STB_ACCESS_TOKEN'), 'expire_time' => $expire_time];
         $stringData = serialize($token);
         $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
         $stbAccessToken = base64_encode($iv . openssl_encrypt($stringData, 'aes-256-cbc',  $iv . openssl_cipher_iv_length('aes-256-cbc') . date('YmdH'), 0, $iv));
         
        
        if(Session::has('payment_type')) {
            if(Session::get('payment_type') == 'cart_payment') {
                $combined_order = CombinedOrder::findOrFail(Session::get('combined_order_id'));
                $amount = $combined_order->grand_total;
            }
            elseif (Session::get('payment_type') == 'wallet_payment') {
                $amount = Session::get('payment_data')['amount'];
            }
            elseif (Session::get('payment_type') == 'customer_package_payment') {
                $customer_package = CustomerPackage::findOrFail(Session::get('payment_data')['customer_package_id']);
                $amount = $customer_package->amount;
            }
            elseif (Session::get('payment_type') == 'seller_package_payment') {
                $seller_package = SellerPackage::findOrFail(Session::get('payment_data')['seller_package_id']);
                $amount = $seller_package->amount;
            }
        }
    
        $data  = [
                    'custom' =>''.rand(1000000000,9999999999).'',
                    'currency_code' => 'TRY',
                    'amount' => $amount ,
                    'details' => 'STP Pazar Product',
                    'web_hook' => 'http://yoursite.com/web_hook.php',
                    'cancel_url' => route('stb.payment.cancel'),
                    'success_url' => route('stb.payment.success'),
                ];
                $parameters = '+';
                while(strstr($parameters, '+') == true){
                $stringData = serialize($data);
                $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                $parameters =  base64_encode($iv . openssl_encrypt($stringData, 'aes-256-cbc', $StbSecretKey , 0, $iv));
                }
                $headers = [
                    "Accept: application/json",
                    "Authorization: Bearer {$stbAccessToken}",
                ];
                $url = $stbBaseUrl .'?data=' . $parameters;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      try {
                // Call API with your client and get a response for your call
                $response = json_decode(curl_exec($ch));
                if($response->code == 200){
                    return redirect()->away($response->url) ; 
                }
                curl_close($ch);
        }catch (\Exception $ex) {
            flash(translate('Something was wrong'))->error();
            return redirect()->route('home');
        }
                           
                
    }
    public function success(Request $request)
   {
       $payment_type = Session::get('payment_type');
       $payment = ["status" => "Success"];
                if ($payment_type == 'cart_payment') {
                    return (new CheckoutController)->checkout_done(session()->get('combined_order_id'), json_encode($payment));
                }
                else if ($payment_type == 'wallet_payment') {
                    return (new WalletController)->wallet_payment_done(session()->get('payment_data'), json_encode($payment));
                }
                else if ($payment_type == 'customer_package_payment') {
                    return (new CustomerPackageController)->purchase_payment_done(session()->get('payment_data'), json_encode($payment));
                }
                else if ($payment_type == 'seller_package_payment') {
                    return (new SellerPackageController)->purchase_payment_done(session()->get('payment_data'), json_encode($payment));
                }
                 else {
                flash(translate('Payment failed'))->error();
                return redirect()->route('home');
            }
   }

    public function cancel(Request $request)
   {
        flash(translate('Payment cancelled'))->success();
    	return redirect()->route('home');
   }
}