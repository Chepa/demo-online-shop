<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Индекс для фильтрации активных товаров
            $table->index('is_active', 'products_is_active_index');
            
            // Индекс для фильтрации по категориям
            $table->index('category_id', 'products_category_id_index');
            
            // Композитный индекс для частого запроса: активные товары по категории
            $table->index(['is_active', 'category_id'], 'products_active_category_index');
            
            // Индекс для фильтрации по цене
            $table->index('price', 'products_price_index');
            
            // Индекс для сортировки по дате создания
            $table->index('created_at', 'products_created_at_index');
            
            // FULLTEXT индекс для поиска по названию и описанию (MySQL 5.6+)
            // Для InnoDB нужна версия MySQL 5.6.4+
            if (config('database.default') === 'mysql') {
                $table->fullText(['name', 'description'], 'products_search_index');
            } else {
                // Для других БД используем обычный индекс на name
                $table->index('name', 'products_name_index');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            // Индекс для фильтрации заказов по статусу
            $table->index('status', 'orders_status_index');
            
            // Индекс для поиска заказов пользователя
            $table->index('user_id', 'orders_user_id_index');
            
            // Композитный индекс для частого запроса: заказы пользователя по статусу
            $table->index(['user_id', 'status'], 'orders_user_status_index');
            
            // Индекс для сортировки по дате создания
            $table->index('created_at', 'orders_created_at_index');
        });

        // Индекс для cart_items.user_id уже создан через foreign key, пропускаем

        Schema::table('order_items', function (Blueprint $table) {
            // Индексы для связей
            $table->index('order_id', 'order_items_order_id_index');
            $table->index('product_id', 'order_items_product_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_is_active_index');
            $table->dropIndex('products_category_id_index');
            $table->dropIndex('products_active_category_index');
            $table->dropIndex('products_price_index');
            $table->dropIndex('products_created_at_index');
            
            if (config('database.default') === 'mysql') {
                $table->dropFullText('products_search_index');
            } else {
                $table->dropIndex('products_name_index');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_user_id_index');
            $table->dropIndex('orders_user_status_index');
            $table->dropIndex('orders_created_at_index');
        });

        // Индекс для cart_items.user_id создан через foreign key, не удаляем

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_order_id_index');
            $table->dropIndex('order_items_product_id_index');
        });
    }
};
