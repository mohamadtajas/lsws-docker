<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wallet\OfflineRechageRequest;
use App\Http\Requests\Wallet\RechargeRequest;
use App\Http\Requests\Wallet\RechargeSyCardRequest;
use App\Http\Requests\Wallet\RequestOfflineRechageRequest;
use App\Http\Requests\Wallet\TransferRequest;
use App\Http\Requests\Wallet\UpdateApprovedRequest;
use App\Mail\CustomEmail;
use App\Models\Wallet;
use App\Models\user;
use Auth;
use Illuminate\Support\Facades\Mail;
use Session;

class WalletController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_all_offline_wallet_recharges'])->only('offline_recharge_request');
    }

    public function index()
    {
        $wallets = Wallet::where('user_id', Auth::user()->id)->latest()->paginate(10);
        return view('frontend.user.wallet.index', compact('wallets'));
    }

    public function recharge(RechargeRequest $request)
    {
        $data['amount'] = $request->amount;
        $data['payment_method'] = $request->payment_option;

        $request->session()->put('payment_type', 'wallet_payment');
        $request->session()->put('payment_data', $data);

        $decorator = __NAMESPACE__ . '\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $request->payment_option))) . "Controller";
        if (class_exists($decorator)) {
            return (new $decorator)->pay($request);
        }
    }

    public function recharge_sy_card(RechargeSyCardRequest $request)
    {
        $data['number'] =  str_replace('-', '', $request->card_number);
        $data['serial'] = str_replace('-', '', $request->card_serial);
        $data['stp_user_id'] = auth()->user()->id;
        $data['stp_user_name'] = auth()->user()->name;
        $encryptedData = '+';
        while (strstr($encryptedData, '+') == true || strstr($encryptedData, '/') == true) {
            $stringData = serialize($data);
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            $encryptedData =  base64_encode($iv . openssl_encrypt($stringData, 'aes-256-cbc', 'A2Bw8dz3CPQekA1g4TUF6Rxx3WOpDNeGdS5kHVTL7P9C6ZYf0ROvPt2SIN4b9sQ', 0, $iv));
        }
        $url = env('SY_CARD_URL') . $encryptedData;
        $headers = [
            "Accept: application/json",
            "Authorization: Bearer " .env('SY_CARD_TOKEN'),
            "X-Secret-Key: Hr912GFprrplD4pQYWlLqGbomPI7hIqY",
            "X-Access-Token: E9Gt9cv1ZKGrhE7f1QBP6Tpp1KNsFJePtT7jMXOE0I5C7EUz9HPoRqT3ILJ9z5wH"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        try {
            // Call API with your client and get a response for your call
            $response = json_decode(curl_exec($ch));
            if ($response->code == 200) {
                flash(translate('The wallet has been topped up with a value ') . $response->data->amount)->success();
                return redirect()->back();
            } else {
                flash(translate('Something was wrong'))->error();
                return redirect()->route('home');
            }
            curl_close($ch);
        } catch (\Exception $ex) {
            flash(translate('Something was wrong'))->error();
            return redirect()->route('home');
        }
    }

    public function wallet_payment_done($payment_data, $payment_details)
    {
        $user = Auth::user();
        $user->balance = $user->balance + $payment_data['amount'];
        $user->save();

        $wallet = new Wallet;
        $wallet->user_id = $user->id;
        $wallet->amount = $payment_data['amount'];
        $wallet->payment_method = $payment_data['payment_method'];
        $wallet->payment_details = $payment_details;
        $wallet->save();

        Session::forget('payment_data');
        Session::forget('payment_type');

        flash(translate('Recharge completed'))->success();
        return redirect()->route('wallet.index');
    }

    public function offline_recharge(OfflineRechageRequest $request)
    {
        $wallet = new Wallet;
        $wallet->user_id = Auth::user()->id;
        $wallet->amount = $request->amount;
        $wallet->payment_method = $request->payment_option;
        $wallet->payment_details = $request->trx_id;
        $wallet->approval = 0;
        $wallet->offline_payment = 1;
        $wallet->reciept = $request->photo;
        $wallet->save();
        flash(translate('Offline Recharge has been done. Please wait for response.'))->success();
        return redirect()->route('wallet.index');
    }

    public function offline_recharge_request(RequestOfflineRechageRequest $request)
    {
        $wallets = Wallet::where('offline_payment', 1);
        $type = null;
        if ($request->type != null) {
            $wallets = $wallets->where('approval', $request->type);
            $type = $request->type;
        }
        $wallets = $wallets->paginate(10);
        return view('manual_payment_methods.wallet_request', compact('wallets', 'type'));
    }

    public function updateApproved(UpdateApprovedRequest $request)
    {
        $wallet = Wallet::findOrFail($request->id);
        $wallet->approval = $request->status;
        if ($request->status == 1) {
            $user = $wallet->user;
            $user->balance = $user->balance + $wallet->amount;
            $user->save();
        } else {
            $user = $wallet->user;
            $user->balance = $user->balance - $wallet->amount;
            $user->save();
        }
        if ($wallet->save()) {
            return 1;
        }
        return 0;
    }

    public function transfer(TransferRequest $request)
    {
        $amount = $request->amount;
        $sender_user = Auth::user();
        $sender_wallet = $sender_user->balance;
        $receiver_user = User::where('email', $request->email)->first();
        if ($receiver_user && $receiver_user->id != $sender_user->id) {
            if ($request->amount + env('TRANSFER_TAX_FOR_WALLET') <= $sender_wallet) {
                if ($request->amount < env('MIN_TRANSFER_BER_PROCESS') || $request->amount > env('MAX_TRANSFER_BER_PROCESS')) {
                    flash(translate('The amount must be between ' . env('MIN_TRANSFER_BER_PROCESS') . ' and ' . env('MAX_TRANSFER_BER_PROCESS')))->error();
                    return redirect()->route('home');
                }

                $sender_user->balance = $sender_user->balance - ($amount + env('TRANSFER_TAX_FOR_WALLET'));
                $sender_user->save();

                $receiver_user->balance += $amount;
                $receiver_user->save();

                $admin = get_admin();
                $admin->balance += env('TRANSFER_TAX_FOR_WALLET');
                $admin->save();

                $wallet = new Wallet;
                $wallet->user_id = $sender_user->id;
                $wallet->amount = ($request->amount + env('TRANSFER_TAX_FOR_WALLET')) * -1;
                $wallet->payment_method = 'inner transfer';
                $wallet->payment_details =  translate('You have sent') . ' ' . $request->amount . ' ' . get_system_default_currency()->code . ' ' . translate('to') . ' ' . $receiver_user->email;
                $wallet->save();


                $wallet = new Wallet;
                $wallet->user_id = $receiver_user->id;
                $wallet->amount = $request->amount;
                $wallet->payment_method = 'inner transfer';
                $wallet->payment_details = translate('You have received') . ' ' . $request->amount . ' ' . get_system_default_currency()->code . ' ' . translate('from') . ' ' . $sender_user->email;
                $wallet->save();

                $subject = 'STP Wallet, ' . translate('Transfer Completed');
                $content = translate('You have received') . ' ' . $request->amount . ' ' . get_system_default_currency()->code . ' ' . translate('from') . ' ' . $sender_user->email;
                Mail::to($receiver_user->email)->send(new CustomEmail($subject, $content));

                $subject = 'STP Wallet, ' . translate('Transfer Completed');
                $content = translate('You have sent') . ' ' . $request->amount . ' ' . get_system_default_currency()->code . ' ' . translate('to') . ' ' . $receiver_user->email;
                Mail::to($sender_user->email)->send(new CustomEmail($subject, $content));


                flash(translate('Transaction completed successfully'))->success();
                return redirect()->back();
            } else {
                flash(translate('Insufficient balance'))->error();
                return redirect()->route('home');
            }
        } else {
            flash(translate('This user does not exist'))->error();
            return redirect()->route('home');
        }
    }
}
