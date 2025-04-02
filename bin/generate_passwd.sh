#!/bin/bash

# File path
PASSWD_FILE="./temp/.passwd"

# check if the variable is set
if [[ -z "$CATALOG_CREDENTIALS" ]]; then
    echo "E|GP|CATALOG_CREDENTIALS is not set"
    exit 1
fi

# Splitting the value of the variable into username and password
IFS=':' read -r USERNAME PASSWORD <<< "$CATALOG_CREDENTIALS"

# Check if both parts are set
if [[ -z "$USERNAME" || -z "$PASSWORD" ]]; then
    echo "E|GP|Unsupported format of CATALOG_CREDENTIALS expected username:password"
    exit 1
fi

# Generating bcrypt hash
HASHED_PASSWORD=$(php -r "echo password_hash('$PASSWORD', PASSWORD_BCRYPT);")

# Save to file (overwrite old password if exists)
echo "$USERNAME:$HASHED_PASSWORD" > "$PASSWD_FILE"

# Set permissions (read only for owner)
chmod 640 "$PASSWD_FILE"
chown -R www-data:www-data "$PASSWD_FILE"

echo "I|GP|File ${PASSWD_FILE} has been successfully created!"
