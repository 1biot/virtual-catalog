
FROM php:8.3-fpm

RUN apt-get update \
    && apt-get install -y unzip curl ca-certificates git zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


WORKDIR /app
COPY . .

RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader || true; fi

# Build-time variables
ARG CATALOG_CREDENTIALS
ARG CATALOG_PRODUCT_SHOPTET_XML_URL

# Propagate to runtime
ENV CATALOG_CREDENTIALS=${CATALOG_CREDENTIALS}
ENV CATALOG_PRODUCT_SHOPTET_XML_URL=${CATALOG_PRODUCT_SHOPTET_XML_URL}

RUN ./bin/build.sh

CMD ["php-fpm"]
