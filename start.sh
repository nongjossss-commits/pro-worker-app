#!/bin/bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan db:seed --class=StabilityTestSeeder
php artisan serve > server.log 2>&1 &
