#!/bin/bash

PACKAGE_NAME="camat_update_v3.2.tar.gz"

echo "Creating deployment package: $PACKAGE_NAME"

# Remove old package if exists
rm -f $PACKAGE_NAME

# Tar the structure
tar -czvf $PACKAGE_NAME \
    *.php \
    .htaccess \
    config/ \
    helpers/ \
    modules/ \
    includes/ \
    assets/ \
    DEPLOYMENT_INSTRUCTIONS_V3.md

echo "-----------------------------------"
echo "Package Created Successfully!"
echo "File: $PACKAGE_NAME"
ls -lh $PACKAGE_NAME
