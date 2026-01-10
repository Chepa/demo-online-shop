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
# Установка PHP зависимостей (если вы не устанавливали Laravel а он уже был в репозитории)
docker exec app composer install

# Установка npm зависимостей
docker exec app npm i

# сборка проекта 
docker exec app npm build

# Применение миграций
docker exec app php artisan migrate
```

## Доступ к приложению
- **Веб-интерфейс**: http://localhost

## Тестирование
docker exec app php artisan test

## OpenAPI документация
http://localhost/swagger

## Описание

# demo-online-shop
