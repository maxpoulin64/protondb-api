#!/bin/bash
cd reports
rm *.json
tar -xvf "$(ls -t | head -n1)"
cp *.json ../../webroot/reports.json

cd ../../
php -f scripts/merge-db.php
