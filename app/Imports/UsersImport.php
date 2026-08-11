<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    /**
     * Ожидаемые колонки: name, second_name, email, phone, position,
     * department_name, roles (через запятую), password (опц.), is_active (да/нет)
     */
    public function collection(Collection $collection): void
    {
        DB::transaction(function () use ($collection) {
            foreach ($collection as $row) {
                $email = trim((string) ($row['email'] ?? ''));

                if ($email === '') {
                    continue;
                }

                $departmentId = null;
                $departmentName = trim((string) ($row['department_name'] ?? ''));

                if ($departmentName !== '') {
                    $departmentId = Department::query()
                        ->firstOrCreate(['name' => $departmentName])
                        ->id;
                }

                $isActiveRaw = mb_strtolower(trim((string) ($row['is_active'] ?? 'да')));
                $isActive = !in_array($isActiveRaw, ['нет', 'no', '0', 'false'], true);

                $existingUser = User::query()->where('email', $email)->first();

                $attributes = [
                    'name' => trim((string) ($row['name'] ?? '')),
                    'second_name' => trim((string) ($row['second_name'] ?? '')),
                    'phone' => trim((string) ($row['phone'] ?? '')),
                    'position' => trim((string) ($row['position'] ?? '')),
                    'department_id' => $departmentId,
                    'is_active' => $isActive,
                ];

                $password = trim((string) ($row['password'] ?? ''));

                if ($password !== '') {
                    $attributes['password'] = $password; // модель сама хэширует (cast 'hashed')
                } elseif (!$existingUser) {
                    $attributes['password'] = Str::random(12);
                }

                $user = User::query()->updateOrCreate(['email' => $email], $attributes);

                $rolesRaw = trim((string) ($row['roles'] ?? ''));

                if ($rolesRaw !== '') {
                    $roleNames = array_values(array_filter(array_map('trim', explode(',', $rolesRaw))));

                    if (!empty($roleNames)) {
                        $user->syncRoles($roleNames);
                    }
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'name' => 'ФИО',
            'email' => 'Email',
        ];
    }
}
