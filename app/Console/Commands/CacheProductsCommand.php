<?php

namespace App\Console\Commands;

use App\Services\Catalog\ProductCacheService;
use App\Services\Catalog\ProductRedisService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;

class CacheProductsCommand extends Command
{
    protected $signature = 'products:cache';
    protected $description = 'Кэшировать все товары';

    public function handle(ProductRedisService $service): int
    {
        $this->info('Начинаем кэширование товаров...');

        try {
            $service->cacheAll();

            $this->info('Товары успешно добавлены в кэш');

            return CommandAlias::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Ошибка при кэшировании товаров: ' . $e->getMessage());

            return CommandAlias::FAILURE;
        }
    }
}
