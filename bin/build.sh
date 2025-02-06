#!/bin/bash

source ./bin/generate_passwd.sh

CATALOG_PRODUCT_SHOPTET_XML_URL=${CATALOG_PRODUCT_SHOPTET_XML_URL:-""}
source ./bin/download.sh "${CATALOG_PRODUCT_SHOPTET_XML_URL}"
