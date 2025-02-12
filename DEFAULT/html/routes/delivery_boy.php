<?php

/*
|--------------------------------------------------------------------------
| POS Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


//Admin

use App\Http\Controllers\DeliveryBoyController;
use App\Http\Controllers\OrderController;
use App\Models\OrderDetail;

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin', 'prevent-back-history']], function () {
    //Delivery Boy
    Route::resource('delivery-boys', DeliveryBoyController::class);

    Route::controller(DeliveryBoyController::class)->group(function () {
        Route::get('/delivery-boy/ban/{id}', 'ban')->name('delivery-boy.ban');
        Route::get('/delivery-boy-configuration', 'delivery_boy_configure')->name('delivery-boy-configuration');
        Route::post('/delivery-boy/order-collection', 'order_collection_form')->name('delivery-boy.order-collection');
        Route::post('/collection-from-delivery-boy', 'collection_from_delivery_boy')->name('collection-from-delivery-boy');
        Route::post('/delivery-boy/delivery-earning', 'delivery_earning_form')->name('delivery-boy.delivery-earning');
        Route::post('/paid-to-delivery-boy', 'paid_to_delivery_boy')->name('paid-to-delivery-boy');
        Route::get('/delivery-boys-payment-histories', 'delivery_boys_payment_histories')->name('delivery-boys-payment-histories');
        Route::get('/delivery-boys-collection-histories', 'delivery_boys_collection_histories')->name('delivery-boys-collection-histories');
        Route::get('/delivery-boy/cancel-request', 'cancel_request_list')->name('delivery-boy.cancel-request');
    });
});

Route::group(['middleware' => ['user', 'verified', 'unbanned', 'prevent-back-history']], function () {
    Route::controller(DeliveryBoyController::class)->group(function () {
        Route::get('/assigned-deliveries', 'assigned_delivery')->name('assigned-deliveries');
        Route::get('/pickup-deliveries', 'pickup_delivery')->name('pickup-deliveries');
        Route::get('/on-the-way-deliveries', 'on_the_way_deliveries')->name('on-the-way-deliveries');
        Route::get('/completed-deliveries', 'completed_delivery')->name('completed-deliveries');
        Route::get('/pending-deliveries', 'pending_delivery')->name('pending-deliveries');
        Route::get('/cancelled-deliveries', 'cancelled_delivery')->name('cancelled-deliveries');
        Route::get('/total-collections', 'total_collection')->name('total-collection');
        Route::get('/total-earnings', 'total_earning')->name('total-earnings');
        Route::get('/cancel-request/{id}', 'cancel_request')->name('cancel-request');
        Route::get('/cancel-request-list', 'delivery_boys_cancel_request_list')->name('cancel-request-list');
    });

    Route::controller(OrderController::class)->group(function () {
        Route::post('/orders/update_delivery_status', 'update_delivery_status')->name('delivery-boy.orders.update_delivery_status');
    });

    Route::controller(DeliveryBoyController::class)->group(function () {
        Route::get('/delivery-boy/order-detail/{id}', 'order_detail')->name('delivery-boy.order-detail');
    });
});

Route::group(['prefix' => 'api-delivery', 'middleware' => ['stl']], function () {
    //For STL
    Route::get('/get-tracking-number/{orderNumber}/{productId}/{urunNo}', function ($orderNumber, $productId, $urunNo) {
        try {
            $products = trendyol_order_details($orderNumber);
            if (!empty($products)) {
                $products = trendyol_order_details($orderNumber)['products'];
                foreach ($products as $product) {
                    if ($product['productId'] == $productId && $product['urunNo'] == $urunNo) {
                        return response()->json([
                            'success' => true,
                            'tracking_number' => $product['kargoTakipNo']
                        ]);
                    }
                }
                return response()->json([
                    'success' => false,
                    'tracking_number' => null
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erorr in web Routes : ', [$e->getMessage()]);
        }
    });

    Route::get('/update-delivery-code/{tracking_number}/{delivery_code}', function ($tracking_number, $delivery_code) {
        $order_details = OrderDetail::where('tracking_code', $tracking_number)->first();
        if ($order_details) {
            $order_details->delivery_code = $delivery_code;
            $order_details->save();
        }
    });

    Route::get('/update-delivery-status/{tracking_number}', function ($tracking_number) {
        $order_details = OrderDetail::where('tracking_code', $tracking_number)->first();
        if ($order_details) {
            $order_details->delivery_status = 'delivered';
            $order_details->save();
        }
    });
});
