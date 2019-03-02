# ARG BASED TAG won't work until the docker version is updated. Use explict repo for now
FROM nginx:alpine

ARG TAG=latest
#FROM nginx:${TAG}

COPY ./conf/nginx /etc/nginx/conf.d/
