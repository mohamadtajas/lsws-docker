<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Wallet\OfflineRechageRequest;
use App\Http\Requests\Wallet\RechargeSyCardRequest;
use App\Http\Requests\Wallet\TransferRequest;
use App\Http\Requests\Wishlist\ProcessPaymentRequest;
use App\Http\Resources\V2\WalletCollection;
use App\Mail\CustomEmail;
use App\Models\Cart;
use App\Models\User;
use App\Models\Wallet;
use App\Models\CombinedOrder;
use App\Models\Provider;
use App\Models\TrendyolOrder;
use App\Models\TrendyolMerchant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WalletController extends Controller
{
    public function balance()
    {
        $user = User::find(auth()->user()->id);
        $latest = Wallet::where('user_id', auth()->user()->id)->latest()->first();
        return response()->json([
            'balance' => single_price($user->balance),
            'last_recharged' => $latest == null ? "Not Available" : $latest->created_at->diffForHumans(),
        ]);
    }

    public function walletRechargeHistory()
    {
        return new WalletCollection(Wallet::where('user_id', auth()->user()->id)->latest()->paginate(10));
    }

    public function processPayment(ProcessPaymentRequest $request)
    {
        $order = new OrderController;
        $user = User::find(auth()->user()->id);
        $amount = Cart::where('user_id', $user->id)
            ->selectRaw('SUM(price * quantity + tax + shipping_cost) as total')
            ->value('total');
        Log::info('before check the amount of order was the balance of user : ' . auth()->user()->id . ' is ' . $user->balance . ' and he send amount ' . $request->amount . ' and order amount : ' . $amount);
        if ($user->balance >= $amount) {

            $response =  $order->store($request, true);
            $decoded_response = $response->original;
            if ($decoded_response['result'] == true) {
                if (get_setting('invite_system') == 1 && $user->invitation->count() == 1) {
                    $invite_discount = env('INVITE_DISCOUNT');

                    $invitation = $user->invitation->first();
                    $invited_by_user = $invitation->user;

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
                    $wallet->user_id = auth()->user()->id;
                    $wallet->amount = $invite_discount;
                    $wallet->payment_method = 'invite';
                    $wallet->payment_details =  translate('You have received') . ' ' . $invite_discount . ' ' . get_system_default_currency()->code . ' ' . translate('by invitation from') . ' ' . $invited_by_user->email;
                    $wallet->save();

                    $invitation->used = 1;
                    $invitation->save();
                }

                $combined_order = CombinedOrder::findOrFail($decoded_response['combined_order_id']);
                $user->balance -= $combined_order->grand_total;
                $user->save();
                Log::info('after check the amount of order was the balance of user : ' . $user->id . ' is ' . $user->balance . ' and he send amount ' . $request->amount . ' and order amount : ' . $amount);
            } else {
                return response()->json(['result' => false, 'message' => $decoded_response['message']]);
            }

            $accessToken = trendyol_account_login();
            $trendyol_products = [];
            $trendyol_array = [];

            $address = json_decode($combined_order->shipping_address);

            $receiver_name = $address->name;
            $receiver_email = auth()->user()->email;
            $receiver_id_number = $address->id_number;
            $receiver_phone = $address->phone;
            $receiver_address = $address->address;
            $receiver_city = $address->city;

            foreach ($combined_order->orders as $key => $order) {
                $trendyol_products = [];
                foreach ($order->orderDetails as $orderDetail) {
                    if ($orderDetail->trendyol == 1) {
                        $product = trendyol_product_details($accessToken, $orderDetail->product_id, $orderDetail->urunNo);
                        $trendyol_array[$orderDetail->product_id][$orderDetail->urunNo] = $product;
                        array_push($trendyol_products, [
                            "id" => $product['id'],
                            "kampanyaID" => $product['kampanyaID'],
                            "listeID" => $product['listeID'],
                            "saticiID" => $product['shopId'],
                            "adet" => $orderDetail->quantity
                        ]);
                        $trendyolMerchant = TrendyolMerchant::where('trendyol_id', $product['shopId'])->first();
                        if (!$trendyolMerchant) {
                            $trendyolMerchant = new TrendyolMerchant();
                            $trendyolMerchant->trendyol_id = $product['shopId'];
                            $trendyolMerchant->name = $product['shopName'];
                            $trendyolMerchant->official_name = $product['shopOfficialName'];;
                            $trendyolMerchant->email = $product['shopEmail'];
                            $trendyolMerchant->tax_number = $product['shopTaxNumber'];
                            $trendyolMerchant->save();
                        }
                        $orderDetail->trendyol_merchant_id = $trendyolMerchant->id;
                        $orderDetail->product_image = $product['photos'][0];
                        $orderDetail->product_name = $product['name'];
                        $orderDetail->category_name = trendyol_category($product['originalCategory'])['ar_name'];
                        $orderDetail->save();
                        $order_details[$product['id']][$product['kampanyaID']][$product['listeID']][$product['shopId']] = $orderDetail->id;
                    } elseif ($orderDetail->trendyol == 0 && $orderDetail->provider_id == null) {
                        $product = $orderDetail->product;
                        $photos = explode(',', $product->photos);
                        $orderDetail->product_image = $photos[0] ?? null;
                        $orderDetail->product_name = $product->name;
                        $orderDetail->category_name = $product->main_category->name ?? null;
                        $orderDetail->save();
                    } elseif ($orderDetail->provider_id != null) {
                        if ($orderDetail->provider->service()->checkBalance($orderDetail->price * $orderDetail->quantity)) {
                            $externalOrder = $orderDetail->provider->service()->buy($orderDetail->product_id);
                            $orderDetail->external_order_id = $externalOrder->orderId;
                            $orderDetail->save();
                        }
                    }
                }
                if (count($trendyol_products) > 0) {
                    $trendyol_purchase = trendyol_purchase($accessToken, $trendyol_products);
                    if (count($trendyol_purchase) > 0) {
                        foreach ($trendyol_purchase['orderDetail'] as $trendyolOrderDetails) {
                            TrendyolOrder::firstOrCreate([
                                'order_id' => $order->id,
                                'order_detail_id'       => $order_details[$trendyolOrderDetails['id']][$trendyolOrderDetails['kampanyaID']][$trendyolOrderDetails['listeID']][$trendyolOrderDetails['saticiID']],
                                'trendyol_orderNumber'  => $trendyol_purchase['orderNumber'],
                                'trendyol_product_id'   =>  $trendyolOrderDetails['id'],
                                'trendyol_kampanyaID'   =>  $trendyolOrderDetails['kampanyaID'],
                                'trendyol_listeID'      =>  $trendyolOrderDetails['listeID'],
                                'trendyol_saticiID'     =>  $trendyolOrderDetails['saticiID'],
                                'trendyol_adet'         =>  $trendyolOrderDetails['adet'],
                                'trendyol_success'      =>  $trendyolOrderDetails['success']
                            ]);
                        }
                    }
                }
                calculateCommissionAffilationClubPoint($order);
            }

            foreach ($combined_order->orders as $key => $order) {
                $delivery_type = ($order->shipping_type == 'home_delivery') ? 'home_delivery' : 'pickup_center';
                $note = $order->additional_info;
                foreach ($order->orderDetails as $orderDetail) {
                    if ($orderDetail->trendyol == 1) {
                        $product = $trendyol_array[$orderDetail->product_id][$orderDetail->urunNo];
                        $trendyolOrder = TrendyolOrder::where('order_id', $order->id)->where('order_detail_id', $orderDetail->id)->first();
                        $parcel_name = $product['name'];
                        $parcel_url =  route('trendyol-product', ['id' => $product['id'], 'urunNo' => $product['urunNo']]);
                        $parcel_image = $product['photos'][0];
                        $product_id = $product['id'];
                        $urunNo = $product['urunNo'];
                        $parcel_price = (float) str_replace(',', '', $product['new_price']);
                        $quantity = $orderDetail->quantity;
                        $order_number = $order->code;
                        $trendyol_order_number = $trendyolOrder->trendyol_orderNumber;
                    } elseif ($orderDetail->trendyol == 0 && $orderDetail->provider_id == null) {
                        $product = $orderDetail->product;
                        $image = null;
                        if ($product->photos != null) {
                            $photos = explode(',', $product->photos);
                            $image = uploaded_asset($photos[0]);
                        }
                        $parcel_name = $product->name;
                        $parcel_url =  route('product', $product->slug);
                        $parcel_image = $image;
                        $product_id = $orderDetail->product_id;
                        $urunNo = $orderDetail->urunNo;
                        $parcel_price = $orderDetail->price;
                        $quantity = $orderDetail->quantity;
                        $order_number = $order->code;
                        $trendyol_order_number = 0;
                    } elseif ($orderDetail->provider_id != null) {
                        $provider = Provider::find($orderDetail->provider_id);
                        $parcel_name = $orderDetail->product_name;
                        $parcel_url =  route('product.provider', [strtolower($provider->name), $orderDetail->product_id]);
                        $parcel_image = $orderDetail->product_image;
                        $product_id = $orderDetail->product_id;
                        $urunNo = $orderDetail->urunNo;
                        $parcel_price = $orderDetail->price;
                        $quantity = $orderDetail->quantity;
                        $order_number = $order->code;
                        $trendyol_order_number = 0;
                    }
                    if ($trendyol_order_number >= 0 && $orderDetail->digital == 0) {
                        $data = [
                            'parcel_name' => $parcel_name,
                            'parcel_url' => $parcel_url,
                            'parcel_image' => $parcel_image,
                            'product_id' => $product_id,
                            'urunNo' => $urunNo,
                            'parcel_price' => $parcel_price,
                            'quantity' => $quantity,
                            'order_number' => $order_number,
                            'trendyol_order_number' => $trendyol_order_number,
                            'receiver_name' => $receiver_name,
                            'receiver_email' => $receiver_email,
                            'receiver_id_number' => $receiver_id_number,
                            'receiver_phone' => $receiver_phone,
                            'receiver_address' =>  $receiver_address,
                            'receiver_city' => $receiver_city,
                            'delivery_type' =>  $delivery_type,
                            'note' => $note
                        ];
                        $response = stl_shipment($data);
                        if ($response->success == true) {
                            $tracking_number = $response->data->tracking_number;
                            $orderDetail->tracking_code = $tracking_number;
                            $orderDetail->delivery_status = 'confirmed';
                            $orderDetail->save();
                        }
                    }
                }
            }

            return $response;
        } else {
            return response()->json([
                'result' => false,
                'combined_order_id' => 0,
                'message' => translate('Insufficient wallet balance')
            ]);
        }
    }

    public function offline_recharge(OfflineRechageRequest $request)
    {
        $wallet = new Wallet;
        $wallet->user_id = auth()->user()->id;
        $wallet->amount = $request->amount;
        $wallet->payment_method = $request->payment_option;
        $wallet->payment_details = $request->trx_id;
        $wallet->approval = 0;
        $wallet->offline_payment = 1;
        $wallet->reciept = $request->photo;
        $wallet->save();
        // flash(translate('Offline Recharge has been done. Please wait for response.'))->success();
        //return redirect()->route('wallet.index');
        return response()->json([
            'result' => true,
            'message' => translate('Offline Recharge has been done. Please wait for response.')
        ]);
    }

    public function recharge_sy_card(RechargeSyCardRequest $request)
    {
        $data['number'] = $request->card_number;
        $data['serial'] = $request->card_serial;
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
            "Authorization: Bearer " . env('SY_CARD_TOKEN'),
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
                return response()->json([
                    'result' => true,
                    'message' => translate('The wallet has been topped up with a value : ') . $response->data->amount
                ]);
            } else {
                return response()->json([
                    'result' => false,
                    'message' => translate('Something was wrong')
                ]);
            }
            curl_close($ch);
        } catch (\Exception $ex) {
            return response()->json([
                'result' => false,
                'message' => translate('Something was wrong')
            ]);
        }
    }

    public function transfer(TransferRequest $request)
    {
        $amount = $request->amount;
        $sender_user = User::find(auth()->user()->id);
        $sender_wallet = $sender_user->balance;
        $receiver_user = User::where('email', $request->email)->first();
        if ($receiver_user && $receiver_user->id != $sender_user->id) {
            if ($request->amount + env('TRANSFER_TAX_FOR_WALLET') <= $sender_wallet) {
                if ($request->amount < env('MIN_TRANSFER_BER_PROCESS') || $request->amount > env('MAX_TRANSFER_BER_PROCESS')) {
                    return response()->json([
                        'result' => false,
                        'message' => translate('The amount must be between ' . env('MIN_TRANSFER_BER_PROCESS') . ' and ' . env('MAX_TRANSFER_BER_PROCESS'))
                    ]);
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

                return response()->json([
                    'result' => true,
                    'message' => translate('Transaction completed successfully')
                ]);
            } else {
                return response()->json([
                    'result' => false,
                    'message' => translate('Insufficient balance')
                ]);
            }
        } else {
            return response()->json([
                'result' => false,
                'message' => translate('This user does not exist')
            ]);
        }
    }

    public function transfer_fee()
    {
        return response()->json([
            'result' => true,
            'message' => env('TRANSFER_TAX_FOR_WALLET')
        ]);
    }
}
