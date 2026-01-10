<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    const int PRODUCTS_COUNT = 15;

    private array $categories = [
        'Электроника',
        'Дом и кухня',
        'Спорт',
        'Одежда',
        'Красота',
    ];

    public function run(): void
    {
        $categories = collect($this->categories)->map(function (string $name) {
            return Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        });

        $faker = fake('ru_RU');
        $categoriesCount = $categories->count();

        for ($i = 0; $i < self::PRODUCTS_COUNT; $i++) {
            $name = $faker->words(3, true);

            Product::create([
                'category_id' => $categories[$i % $categoriesCount]->id,
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::random(6),
                'description' => $faker->sentence(8),
                'price' => $faker->randomFloat(2, 500, 50000),
                'stock' => $faker->numberBetween(5, 50),
                'is_active' => true,
            ]);
        }
    }
}


