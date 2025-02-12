<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvitationReportExport implements FromQuery, WithHeadings, WithMapping
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
            translate('Invited User'),
            translate('Invited By User'),
            translate('Usage Status'),
            translate('Date'),
        ];
    }

    public function map($invitation): array
    {
        $index = 1;
        return [
            $index++,
            $invitation->invited_user,
            $invitation->invited_by_user,
            $invitation->used == 0 ? translate('Unused') : translate('Used'),
            date('d-m-Y', strtotime($invitation->created_at)),
        ];
    }
}
