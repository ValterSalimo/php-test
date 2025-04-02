#!/bin/sh

# Install PHPUnit and dependencies inside the container
docker-compose exec php composer require --dev phpunit/phpunit ^9.5

# Create test directory if it doesn't exist
docker-compose exec php mkdir -p /server/http/tests/Unit/Model
docker-compose exec php mkdir -p /server/http/tests/Unit/Core
docker-compose exec php mkdir -p /server/http/tests/Unit/Controller

# Run the tests
docker-compose exec php vendor/bin/phpunit
