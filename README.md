![Build status](https://travis-ci.org/hammerspacecouk/php-pure-helpers.svg?branch=master)

# Saxopholis (API)

Loading...

## Set up local dev environment

On Windows:
```
Invoke-Expression -Command (aws ecr get-login --no-include-email --region eu-west-2)
```

```
docker-compose up -d
```

## Add commit hook

```
cp bin/hooks/pre-commit .git/hooks

cd .git/hooks
ln -s ../../bin/hooks/pre-commit.sh pre-commit
```

## Data for local
```bash
docker-compose exec cargo-api-php php bin/console game:init:make-ports build/Port.csv &&  docker-compose exec cargo-api-php php bin/console game:init:make-channels build/Channel.csv && docker-compose exec cargo-api-php php bin/console game:init:make-coords && docker-compose exec cargo-api-php php bin/console game:admin:map {SECRET}
```
