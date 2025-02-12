<?php

namespace App\Exports;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromQuery, WithHeadings, WithMapping
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
        $headings = [
            translate('Order Code'),
            translate('Trendyol Order Code'),
            translate('Num. of Products'),
            translate('Num. of Items'),
            translate('Customer'),
            translate('Phone'),
            translate('Seller'),
            translate('Amount'),
        ];
        if (Auth::user()->can('trendyol_earning')) {
            $headings[] = translate('Trendyol Amount');
            $headings[] = translate('Earning');
        }
        $headings = array_merge($headings, [
            translate('Currency'),
            translate('Delivery Status'),
            translate('Payment method'),
            translate('Payment Status'),
        ]);

        return $headings;
    }


    public function map($order): array
    {
        $data = [
            "\t" . $order->code,
            (!empty($order->trendyol_order->first())) ? $order->trendyol_order->first()->trendyol_orderNumber : '',
            count($order->orderDetails),
            $order->orderDetails->sum('quantity'),
            ($order->user != null) ? $order->user->name : 'Guest (' . $order->guest_id . ')',
            json_decode($order->shipping_address)->phone ?? '',
            ($order->shop != null) ? $order->shop->name : translate('Inhouse Order'),
            $order->grand_total,
        ];
        if (Auth::user()->can('trendyol_earning')) {
            $data[] = $order->grand_total_trendyol;
            $data[] = $order->grand_total - $order->grand_total_trendyol;
        }
        $data = array_merge($data, [
            currency_symbol(),
            translate(ucfirst(str_replace('_', ' ', $order->delivery_status))),
            translate(ucfirst(str_replace('_', ' ', $order->payment_type))),
            ($order->payment_status == 'paid') ? translate('Paid') : translate('Unpaid'),
        ]);

        return $data;
    }


    public function chunkSize(): int
    {
        return 200;
    }
}
