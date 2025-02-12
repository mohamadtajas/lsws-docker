<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WalletReportExport implements FromQuery, WithHeadings, WithMapping
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
            '#',
            translate('Customer'),
            translate('Date'),
            translate('Amount'),
            translate('Currency'),
            translate('Payment Method'),
            translate('Payment Details'),
            translate('Approval')
        ];
    }

    public function map($wallet): array
    {
        $index = 1;
        return [
            $index++,
            $wallet->user ? $wallet->user->name : translate('User Not found'),
            date('d-m-Y', strtotime($wallet->created_at)),
            $wallet->amount,
            currency_symbol(),
            ucfirst(str_replace('_', ' ', $wallet->payment_method)),
            $wallet->payment_details,
            $wallet->offline_payment
                ? ($wallet->approval ? translate('Approved') : translate('Pending'))
                : (($wallet->payment_method == 'SY Card' || $wallet->payment_method == 'inner transfer')
                    ? translate('Approved')
                    : 'N/A')
        ];
    }
}
