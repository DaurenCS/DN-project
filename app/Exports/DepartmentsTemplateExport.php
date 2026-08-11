<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DepartmentsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['Отдел продаж', ''],
            ['Отдел разработки', 'Отдел продаж'],
        ];
    }

    public function headings(): array
    {
        return ['name', 'parent_name'];
    }
}
