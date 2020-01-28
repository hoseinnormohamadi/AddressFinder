#!/bin/bash

cp .env.example .env

# config name database
sed -i -e 's/DB_DATABASE=laravel//g' .env
echo -n "Enter a database name > "
read database
sed -i "12i  DB_DATABASE=$database" .env

# config username
sed -i -e 's/DB_USERNAME=root//g' .env
echo -n "Enter a  username > "
read username
sed -i "12i  DB_USERNAME=$username" .env

# config password
sed -i -e 's/DB_PASSWORD=//g' .env
echo -n "Enter  password > "
read password
sed -i "12i  DB_PASSWORD=$password" .env
echo "Composer try to intsall project dependency"
sudo composer install
echo "Server Ready"
sudo php artisan serve &
echo "Migration Started"
sudo php artisan migrate &
echo "migration Finished Successfuly"
echo "Queue Started"
sudo php artisan queue:work --daemon --timeout=3000 &
