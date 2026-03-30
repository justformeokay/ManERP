<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Main categories with subcategories
        $categories = [
            [
                'name' => 'Raw Materials',
                'slug' => 'raw-materials',
                'description' => 'Basic materials and components',
                'children' => [
                    ['name' => 'Steel', 'slug' => 'steel'],
                    ['name' => 'Plastic', 'slug' => 'plastic'],
                    ['name' => 'Rubber', 'slug' => 'rubber'],
                    ['name' => 'Aluminum', 'slug' => 'aluminum'],
                ]
            ],
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic components and devices',
                'children' => [
                    ['name' => 'Semiconductors', 'slug' => 'semiconductors'],
                    ['name' => 'Resistors', 'slug' => 'resistors'],
                    ['name' => 'Capacitors', 'slug' => 'capacitors'],
                ]
            ],
            [
                'name' => 'Finished Goods',
                'slug' => 'finished-goods',
                'description' => 'Completed and packaged products',
                'children' => [
                    ['name' => 'Standard Products', 'slug' => 'standard-products'],
                    ['name' => 'Custom Products', 'slug' => 'custom-products'],
                ]
            ],
            [
                'name' => 'Packaging',
                'slug' => 'packaging',
                'description' => 'Packaging materials and supplies',
                'children' => [
                    ['name' => 'Boxes', 'slug' => 'boxes'],
                    ['name' => 'Labels', 'slug' => 'labels'],
                    ['name' => 'Protective Materials', 'slug' => 'protective-materials'],
                ]
            ],
            [
                'name' => 'Tools & Equipment',
                'slug' => 'tools-equipment',
                'description' => 'Tools, machinery parts, and equipment',
                'children' => [
                    ['name' => 'Hand Tools', 'slug' => 'hand-tools'],
                    ['name' => 'Power Tools', 'slug' => 'power-tools'],
                    ['name' => 'Spare Parts', 'slug' => 'spare-parts'],
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            // Create parent category
            $parentCategory = Category::create($categoryData);

            // Create child categories
            foreach ($children as $child) {
                $child['parent_id'] = $parentCategory->id;
                Category::create($child);
            }
        }
    }
}
