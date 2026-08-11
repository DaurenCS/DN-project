<?php

namespace App\Imports;

use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DepartmentsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    /**
     * Ожидаемые колонки в файле: name, parent_name
     */
    public function collection(Collection $collection): void
    {
        DB::transaction(function () use ($collection) {
            $departmentsByName = [];

            // Первый проход: создаём/находим все департаменты по имени, без родителя
            foreach ($collection as $row) {
                $name = trim((string) ($row['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $departmentsByName[$name] = Department::query()->firstOrCreate(['name' => $name]);
            }

            // Второй проход: проставляем parent_id, когда все департаменты уже существуют
            foreach ($collection as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $parentName = trim((string) ($row['parent_name'] ?? ''));

                if ($name === '' || $parentName === '') {
                    continue;
                }

                $department = $departmentsByName[$name] ?? Department::query()->where('name', $name)->first();
                $parent = $departmentsByName[$parentName] ?? Department::query()->where('name', $parentName)->first();

                if ($department && $parent && $department->id !== $parent->id) {
                    $department->update(['parent_id' => $parent->id]);
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'name' => 'Название',
            'parent_name' => 'Родительский департамент',
        ];
    }
}
