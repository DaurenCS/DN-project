<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['Иванов Иван', 'Иванович', 'ivanov@example.com', '+77011234567', 'Менеджер', 'Отдел продаж', 'curator', '', 'да'],
        ];
    }

    public function headings(): array
    {
        return ['name', 'second_name', 'email', 'phone', 'position', 'department_name', 'roles', 'password', 'is_active'];
    }
}
