<?php

namespace Database\Seeders;

use App\Models\QuestionType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = ['single', 'multiple'];

        foreach ($types as $type) {
            QuestionType::query()->firstOrCreate(['name' => $type]);
        }
    }
}
