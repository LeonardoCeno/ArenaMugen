FROM php:8.2-fpm-alpine AS php
WORKDIR /var/www/html
COPY backend/ ./backend/
COPY frontend/ ./frontend/

FROM nginx:alpine AS nginx
COPY --from=php /var/www/html/frontend/ /var/www/html/frontend/
