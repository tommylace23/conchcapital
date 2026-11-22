#!/bin/bash

# Upload dashboard.html to server
# Usage: ./upload-dashboard.sh

cd /Users/david/Downloads/test

echo "Uploading dashboard.html to server..."
scp -o StrictHostKeyChecking=no dashboard.html deploy@conchcapital.co:/var/www/conchcapital/backend/public/

if [ $? -eq 0 ]; then
    echo "✅ Upload successful!"
    echo ""
    echo "Verifying upload..."
    ssh -o StrictHostKeyChecking=no deploy@conchcapital.co "cd /var/www/conchcapital/backend/public && grep -c 'if (select.disabled)' dashboard.html && echo 'lines:' && wc -l dashboard.html"
else
    echo "❌ Upload failed!"
    exit 1
fi
