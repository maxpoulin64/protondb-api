#!/bin/bash
cd reports
rm *.json
tar -xvf *.tar.gz
cp *.json ../../webroot/reports.json

cd ../../
php -f scripts/merge-db.php
