<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Food', 'Transport', 'Shopping', 'Bills', 'Other'];

        foreach($categories as $category) {
            Category::create([
                'user_id' => 1,
                'name' => $category,
            ]);
        }
    }
}
