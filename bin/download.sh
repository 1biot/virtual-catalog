#!/bin/bash

if [ ! -d "./temp/cache" ]; then
    echo "E|DOWNLOAD|Directory ./temp/cache cannot be created"
    exit 1
fi

if [ -z "$CATALOG_PRODUCT_SHOPTET_XML_URL" ]; then
    echo "E|DOWNLOAD|CATALOG_PRODUCT_SHOPTET_XML_URL is not set"
    exit 1
fi

if [ -f "./temp/cache/products.xml" ]; then
    echo "I|DOWNLOAD|File ./temp/cache/products.xml already exists"
    exit 0
fi

curl -L --max-time 120 --connect-timeout 15 -s \
    -A "Mozilla/5.0" \
    -o "./temp/cache/products.xml" \
    "${CATALOG_PRODUCT_SHOPTET_XML_URL}"

echo "I|DOWNLOAD|File ./temp/cache/products.xml downloaded"
