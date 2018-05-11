# Tests

This is based on https://github.com/chriszarate/docker-compose-wordpress, where details
are given. Below, only quick reference is given.

## Setting up the environment

This will start the necessary services (web server, mysql) and install the tests

```sh
# Starts the docker service
docker-compose -f docker-compose.phpunit.yml up -d
# install tests
docker-compose -f docker-compose.phpunit.yml run --rm wordpress_phpunit /app/bin/install-wp-tests.sh wordpress_test root '' mysql_phpunit latest true
```

To stop the docker services

```sh
docker-compose down
```

## Running the tests


```sh
docker-compose -f docker-compose.phpunit.yml run --rm wordpress_phpunit phpunit
```