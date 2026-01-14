## Online Shop

### 1. Запуск Docker-контейнеров

```bash
docker compose up -d
```

### 2. Настройка переменных окружения

```bash
cp src/.env.example src/.env
```

### 5. Установка зависимостей Laravel

```bash
# Установка PHP зависимостей
docker exec app composer install

# Установка npm зависимостей
docker exec app npm i

# сборка проекта 
docker exec app npm run build

# Применение миграций
docker exec app php artisan migrate

# Добавление товаров и категорий
docker exec app php artisan db:seed --class=ProductCategorySeeder
```

## Доступ к приложению
- **Веб-интерфейс**: http://localhost

## Тестирование
docker exec app php artisan test

## OpenAPI документация
http://localhost/swagger

## Описание
В проекте есть пользовательская часть и админ панель. 
Три уровня пользователей: гость, пользователь и администратор.
Для гостя доступен каталог, для пользователя каталог, страница заказов и возможность оформления заказа. Для админа это каталог, страница заказов, возможность оформления заказа и админ часть с возможностью редактирования товаров, категорий и заказов. 
При регистрации можно указать роль пользователя.
