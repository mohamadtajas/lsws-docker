<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseHistoryItemsCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data) {

                $refund_section = false;
                $refund_button = false;
                $refund_label = "";
                $refund_request_status = 99;
                if (addon_is_activated('refund_request')) {
                    $refund_section = true;
                    $no_of_max_day = get_setting('refund_request_time');
                    $last_refund_date = $data->created_at->addDays($no_of_max_day);
                    $today_date = \Carbon\Carbon::now();
                    if (
                        $data->product != null &&
                        $data->product->refundable != 0 &&
                        $data->refund_request == null &&
                        $today_date <= $last_refund_date &&
                        $data->payment_status == 'paid' &&
                        $data->delivery_status == 'delivered'
                    ) {
                        $refund_button = true;
                    } else if ($data->refund_request != null && $data->refund_request->refund_status == 0) {
                        $refund_label = "Pending";
                        $refund_request_status = $data->refund_request->refund_status;
                    } else if ($data->refund_request != null && $data->refund_request->refund_status == 2) {
                        $refund_label = "Rejected";
                        $refund_request_status = $data->refund_request->refund_status;
                    } else if ($data->refund_request != null && $data->refund_request->refund_status == 1) {
                        $refund_label = "Approved";
                        $refund_request_status = $data->refund_request->refund_status;
                    } else if ($data->product->refundable != 0) {
                        $refund_label = "N/A";
                    } else {
                        $refund_label = "Non-refundable";
                    }
                }
                if ($data->trendyol == 0 && $data->provider_id == null) {
                    $photos = explode(',', $data->product->photos);
                    return [
                        'id' => $data->id,
                        'product_id' => $data->product->id,
                        'product_name' => $data->product->name,
                        'image' => uploaded_asset($photos[0]),
                        'variation' => $data->variation,
                        'price' => format_price(convert_price($data->price)),
                        'tax' => format_price(convert_price($data->tax)),
                        'shipping_cost' => format_price(convert_price($data->shipping_cost)),
                        'coupon_discount' => format_price(convert_price($data->coupon_discount)),
                        'quantity' => (int)$data->quantity,
                        'payment_status' => $data->payment_status,
                        'payment_status_string' => ucwords(str_replace('_', ' ', $data->payment_status)),
                        'delivery_status' => $data->delivery_status,
                        'delivery_status_string' => $data->delivery_status == 'pending' ? "Order Placed" : ucwords(str_replace('_', ' ', $data->delivery_status)),
                        'refund_section' => $refund_section,
                        'refund_button' => $refund_button,
                        'refund_label' => $refund_label,
                        'refund_request_status' => $refund_request_status,
                        'tracking_code' => $data->tracking_code,
                        'tracking_url' =>  env('STL_TRACKING_URL') . $data->tracking_code,
                        'delivery_code' => $data->delivery_code,
                        'trendyol' => 0,
                        'urunNo' => 0
                    ];
                } elseif ($data->trendyol == 1) {
                    return [
                        'id' => $data->id,
                        'product_id' => $data->product_id,
                        'product_name' => $data->product_name,
                        'image' => $data->product_image,
                        'variation' => $data->variation,
                        'price' => format_price(convert_price($data->price)),
                        'tax' => format_price(convert_price($data->tax)),
                        'shipping_cost' => format_price(convert_price($data->shipping_cost)),
                        'coupon_discount' => format_price(convert_price($data->coupon_discount)),
                        'quantity' => (int)$data->quantity,
                        'payment_status' => $data->payment_status,
                        'payment_status_string' => ucwords(str_replace('_', ' ', $data->payment_status)),
                        'delivery_status' => $data->delivery_status,
                        'delivery_status_string' => $data->delivery_status == 'pending' ? "Order Placed" : ucwords(str_replace('_', ' ', $data->delivery_status)),
                        'refund_section' => $refund_section,
                        'refund_button' => $refund_button,
                        'refund_label' => $refund_label,
                        'refund_request_status' => $refund_request_status,
                        'tracking_code' => $data->tracking_code,
                        'tracking_url' =>  env('STL_TRACKING_URL') . $data->tracking_code,
                        'trendyol' => 1,
                        'urunNo' => $data->urunNo,
                        'provider_id' => $data->provider_id
                    ];
                } elseif ($data->provider_id != null) {
                    return [
                        'id' => $data->id,
                        'product_id' => $data->product_id,
                        'product_name' => $data->product_name,
                        'image' => $data->product_image,
                        'variation' => $data->variation,
                        'price' => format_price(convert_price($data->price)),
                        'tax' => format_price(convert_price($data->tax)),
                        'shipping_cost' => format_price(convert_price($data->shipping_cost)),
                        'coupon_discount' => format_price(convert_price($data->coupon_discount)),
                        'quantity' => (int)$data->quantity,
                        'payment_status' => $data->payment_status,
                        'payment_status_string' => ucwords(str_replace('_', ' ', $data->payment_status)),
                        'delivery_status' => $data->delivery_status,
                        'delivery_status_string' => $data->delivery_status == 'pending' ? "Order Placed" : ucwords(str_replace('_', ' ', $data->delivery_status)),
                        'refund_section' => $refund_section,
                        'refund_button' => $refund_button,
                        'refund_label' => $refund_label,
                        'refund_request_status' => $refund_request_status,
                        'tracking_code' => $data->tracking_code,
                        'tracking_url' =>  env('STL_TRACKING_URL') . $data->tracking_code,
                        'trendyol' => 1,
                        'urunNo' => $data->urunNo,
                        'provider_id' => $data->provider_id
                    ];
                }
            })
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}
