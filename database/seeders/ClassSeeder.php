<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            ['class_name' => 'X RPL 1'],
            ['class_name' => 'X RPL 2'],
            ['class_name' => 'XI RPL 1'],
            ['class_name' => 'XI RPL 2'],
            ['class_name' => 'XII RPL 1'],
            ['class_name' => 'XII RPL 2'],
        ];

        foreach ($classes as $class) {
            DB::table('classes')->updateOrInsert(
                ['class_name' => $class['class_name']],
                $class
            );
        }
    }
}