@echo off
echo Configuration de la base de donnees de test...

echo Création de la base de donnees...
php bin/console doctrine:database:create --env=test --if-not-exists

echo Creation du schema...
php bin/console doctrine:schema:create --env=test

echo Chargement des fixtures...
php bin/console doctrine:fixtures:load --env=test --no-interaction

echo Configuration terminee !
pause