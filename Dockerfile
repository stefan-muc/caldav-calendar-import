FROM composer AS builder
WORKDIR /build
COPY . .
RUN composer install

FROM php:apache
COPY --from=builder /build/ /var/www/html/
