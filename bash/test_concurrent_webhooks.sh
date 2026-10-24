#!/bin/bash

# Number of concurrent requests
CONCURRENCY=10

echo "🚀 Sending $CONCURRENCY concurrent requests..."

# We use xargs -P for parallel execution
seq $CONCURRENCY | xargs -P $CONCURRENCY -I {} ./bash/send_webhook_create_order.sh
