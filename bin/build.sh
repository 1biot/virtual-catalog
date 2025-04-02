#!/bin/bash

mkdir -p ./temp ./temp/cache ./log
chmod -R 777 ./temp
chmod 777 ./log

source ./bin/generate_passwd.sh

CATALOG_PRODUCT_SHOPTET_XML_URL=${CATALOG_PRODUCT_SHOPTET_XML_URL:-""}
source ./bin/download.sh "${CATALOG_PRODUCT_SHOPTET_XML_URL}"

#chown -R www-data:www-data ./temp ./log
