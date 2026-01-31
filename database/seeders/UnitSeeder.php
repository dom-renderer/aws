<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Kilogram', 'symbol' => 'Kg', 'text' => 'Standard unit for solid food weight.'],
            ['name' => 'Gram (g)', 'symbol' => 'g', 'text' => 'Used for small quantities of ingredients or snacks.'],
            ['name' => 'Liter (L)', 'symbol' => 'L', 'text' => 'Standard volume unit for liquids like milk, juice, and water.'],
            ['name' => 'Milliliter (mL)', 'symbol' => 'mL', 'text' => 'Used for small volume packaging, e.g., 250mL juice.'],
            ['name' => 'Pack', 'symbol' => 'Pack', 'text' => 'Generic packaging unit for grouped products.'],
            ['name' => 'Bottle', 'symbol' => 'Bottle', 'text' => 'Common unit for beverages and alcoholic drinks.'],
            ['name' => 'Can', 'symbol' => 'Can', 'text' => 'Used for soft drinks, beer, and other canned beverages.'],
            ['name' => 'Carton', 'symbol' => 'Carton', 'text' => 'Packaging unit for milk, juice, or boxed items.'],
            ['name' => 'Case', 'symbol' => 'Case', 'text' => 'Bulk shipping unit typically containing multiple packs or bottles.'],
            ['name' => 'Piece', 'symbol' => 'Piece', 'text' => 'Individual unit, often used for single items or bakery goods.'],
            ['name' => 'Pallet', 'symbol' => 'Pallet', 'text' => 'Pallet.'],
        ];

        DB::table('aw_units')->insert($units);
    }
}
