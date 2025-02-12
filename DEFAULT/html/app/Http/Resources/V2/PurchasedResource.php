<?php

namespace App\Http\Resources\V2;

use App\Models\Provider;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchasedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data =  [
            'id' => $this->id,
            'name' => $this->product_name,
            'thumbnail_image' => $this->product_image,
            'provider_id' => $this->provider_id,
        ];

        if ($this->provider_id != null) {
            $provider = Provider::find($this->provider_id);
            $details = $provider->Service()->orderDetails($this->external_order_id);
            foreach ($details->serials as $serial) {
                $data['details'][] = [
                    'serial_id' => $serial['serialId'],
                    'serial_code' => $provider->Service()->decryptSerial($serial['serialCode']),
                    'valid_to' => $serial['validTo']
                ];
            }
        } else {
            $data['details'] = [];
        }

        return $data;
    }
}
