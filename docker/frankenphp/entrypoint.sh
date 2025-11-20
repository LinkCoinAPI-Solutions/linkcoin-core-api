#!/bin/sh
set -e

echo "Appliquer les migrations"
php bin/console doctrine:migrations:migrate --no-interaction

echo "Charger les fixtures"
php bin/console doctrine:fixtures:load --no-interaction

echo "Démarrer le serveur PHP intégré"
exec php -S 0.0.0.0:8000 -t public/
