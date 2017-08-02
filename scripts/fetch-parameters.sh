#!/usr/bin/env bash

    APP_ENV=prod
# Set the app environment
if [ "$DEPLOYMENT_GROUP_NAME" == "api-alpha" ]
then
    APP_ENV=alpha
else
fi

# Fetch parameters
DB_WRITE_HOST=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/DB_WRITE_HOST --region eu-west-2 --query Parameters[0].Value --output text)
DB_WRITE_USER=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/DB_WRITE_USER --region eu-west-2 --query Parameters[0].Value --output text)
DB_READ_HOST=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/DB_READ_HOST --region eu-west-2 --query Parameters[0].Value --output text)
DB_READ_USER=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/DB_READ_USER --region eu-west-2 --query Parameters[0].Value --output text)
DB_NAME=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/DB_NAME --region eu-west-2 --query Parameters[0].Value --output text)
DB_PORT=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/DB_PORT --region eu-west-2 --query Parameters[0].Value --output text)
TOKEN_AUDIENCE=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/TOKEN_AUDIENCE --region eu-west-2 --query Parameters[0].Value --output text)
TOKEN_COOKIE_NAME=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/TOKEN_COOKIE_NAME --region eu-west-2 --query Parameters[0].Value --output text)
TOKEN_ID=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/TOKEN_ID --region eu-west-2 --query Parameters[0].Value --output text)
TOKEN_ISSUER=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/TOKEN_ISSUER --region eu-west-2 --query Parameters[0].Value --output text)

# Fetch encrypted parameters
DB_WRITE_PASSWORD=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/DB_WRITE_PASSWORD --region eu-west-2 --query Parameters[0].Value --with-decryption  --output text)
DB_READ_PASSWORD=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/DB_READ_PASSWORD --region eu-west-2 --query Parameters[0].Value --with-decryption  --output text)
TOKEN_PRIVATE_KEY=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/TOKEN_PRIVATE_KEY --region eu-west-2 --query Parameters[0].Value --with-decryption  --output text)
OAUTH_GOOGLE_CLIENT_ID=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/OAUTH_GOOGLE_CLIENT_ID --region eu-west-2 --query Parameters[0].Value --with-decryption  --output text)
OAUTH_GOOGLE_CLIENT_SECRET=$(aws ssm get-parameters --names /planet-cargo/${APP_ENV}/OAUTH_GOOGLE_CLIENT_SECRET --region eu-west-2 --query Parameters[0].Value --with-decryption  --output text)

cat >/etc/php-fpm.d/env.conf <<EOL
[www]

env[APP_ENV] = ${APP_ENV}

env[DB_WRITE_HOST] = ${DB_WRITE_HOST}
env[DB_WRITE_USER] = ${DB_WRITE_USER}
env[DB_READ_HOST] = ${DB_READ_HOST}
env[DB_READ_USER] = ${DB_READ_USER}
env[DB_NAME] = ${DB_NAME}
env[DB_PORT] = ${DB_PORT}

env[TOKEN_AUDIENCE] = ${TOKEN_AUDIENCE}
env[TOKEN_COOKIE_NAME] = ${TOKEN_COOKIE_NAME}
env[TOKEN_ID] = ${TOKEN_ID}
env[TOKEN_ISSUER] = ${TOKEN_ISSUER}

env[DB_WRITE_PASSWORD] = ${DB_WRITE_PASSWORD}
env[DB_READ_PASSWORD] = ${DB_READ_PASSWORD}
env[TOKEN_PRIVATE_KEY] = ${TOKEN_PRIVATE_KEY}
env[OAUTH_GOOGLE_CLIENT_ID] = ${OAUTH_GOOGLE_CLIENT_ID}
env[OAUTH_GOOGLE_CLIENT_SECRET] = ${OAUTH_GOOGLE_CLIENT_SECRET}
EOL
