symfony console doctrine:migrations:migrate -n && symfony console doctrine:fixtures:load --append
symfony console doctrine:migrations:migrate --env=test -n && symfony console doctrine:fixtures:load --env=test --append
