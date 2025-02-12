<?php

namespace App\Http\Resources\V2;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WalletCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data) {
                return [
                    'amount' => single_price(($data->amount)),
                    'payment_method' => ucwords(str_replace('_', ' ', $data->payment_method)),
                    'payment_details' => ($data->payment_method == 'SY Card' || $data->payment_method == 'inner transfer')  ? ucwords($data->payment_details) : '' ,
                    'approval_string' => ($data->payment_method == 'SY Card' || $data->payment_method == 'inner transfer')  ? translate('Approved') : ($data->offline_payment ? ($data->approval == 1 ? translate('Approved') : translate('Decliend')) : translate('N/A') ),
                    'date' => Carbon::createFromTimestamp(strtotime($data->created_at))->format('d-m-Y'),
                ];
            })
        ];
    }

    public function with($request)
    {
        return [
            'result' => true,
            'status' => 200
        ];
    }
}
