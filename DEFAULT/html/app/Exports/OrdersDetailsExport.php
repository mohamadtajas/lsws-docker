<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersDetailsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $query;


    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            translate('Order Code'),
            translate('Trendyol Order Code'),
            translate('Tracking Code'),
            translate('Product name'),
            translate('Category name'),
            translate('Num. of Items'),
            translate('Customer'),
            translate('Seller Name'),
            translate('Seller Official Name'),
            translate('Seller Email'),
            translate('Seller Tax Number'),
            translate('Amount'),
            translate('Currency'),
            translate('invoice Number'),
            translate('Delivery Status'),
            translate('Payment method'),
            translate('Payment Status'),
            translate('Date')
        ];
    }

    public function map($ordersReport): array
    {
        $order = $ordersReport->order;
        $merchant_name = $merchant_official_name = $merchant_email = $merchant_tax_number = '';
        if ($order) {
            if ($ordersReport->trendyol == 1) {
                $merchant = $ordersReport->trendyolMerchant;
                if ($merchant) {
                    $merchant_name = $merchant->name;
                    $merchant_official_name = $merchant->official_name;
                    $merchant_email = $merchant->email;
                    $merchant_tax_number = $merchant->tax_number;
                }
            } else {
                if ($order->shop) {
                    $merchant_name = $order->shop->user->name;
                    $merchant_official_name =  $order->shop->name;
                    $merchant_email = $order->shop->user->email;
                    $merchant_tax_number = '';
                } else {
                    $merchant_name = translate('Inhouse Order');
                    $merchant_official_name = translate('Inhouse Order');
                    $merchant_email = translate('Inhouse Order');
                    $merchant_tax_number = translate('Inhouse Order');
                }
            }
            if($ordersReport->trendyol_order != null){
                $trendyol_orderNumber = $ordersReport->trendyol_order->trendyol_orderNumber;
            }else{
                $trendyol_orderNumber = '';
            }
            return [
                "\t" . $order->code, // \t for make it string in excel
                "\t" . $trendyol_orderNumber ?? '',
                "\t" . $ordersReport->tracking_code,
                $ordersReport->product_name,
                $ordersReport->category_name,
                $ordersReport->quantity,
                ($order->user != null) ? $order->user->name : 'Guest (' . $order->guest_id . ')',
                $merchant_name,
                $merchant_official_name,
                $merchant_email,
                $merchant_tax_number,
                $ordersReport->price,
                currency_symbol(),
                $ordersReport->invoice_number,
                translate(ucfirst(str_replace('_', ' ', $ordersReport->delivery_status))),
                translate(ucfirst(str_replace('_', ' ', $order->payment_type))),
                ($ordersReport->payment_status == 'paid') ? translate('Paid') : translate('Unpaid'),
                date('Y-m-d', $order->date)
            ];
        } else {
            return [];
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
