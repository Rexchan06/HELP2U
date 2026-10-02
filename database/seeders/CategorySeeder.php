<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the subject categories students can request help for.
     */
    public function run(): void
    {
        $categories = [
            'Mathematics',
            'Programming',
            'Database Systems',
            'Computer Networks',
            'Software Engineering',
            'Accounting & Finance',
            'Business Management',
            'English & Communication',
            'Statistics',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
