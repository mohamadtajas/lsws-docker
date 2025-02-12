<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerExport implements FromQuery, WithHeadings , WithChunkReading
{
    use Exportable;

    protected $query;
    protected $index;

    public function __construct($query)
    {
        $this->query = $query;
        $this->index = 1;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            '#',
            translate('Name'),
            translate('Email Address'),
            translate('Phone'),
            translate('Default Address Phone'),
            translate('Wallet Balance'),
            translate('Number of Orders'),
            translate('Number of Invitations'),
            translate('All Recharge Amount'),
            translate('Total Expenditure'),
            translate('Total Remaining'),
            translate('Currency'),
            translate('Status'),
            translate('Date Joined'),
        ];
    }

    public function chunkSize(): int
    {
        return 5000;
    }
}
