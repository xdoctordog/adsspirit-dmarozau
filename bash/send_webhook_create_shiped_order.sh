#!/bin/bash

# ==============================================================================
# Configuration
# ==============================================================================
SECRET="paste_the_secret_from_MarketHub_dashboard_here"
# Automatically detect the Windows Host IP for WSL routing
WINDOWS_HOST_IP=$(ip route show default | awk '{print $3}')
URL="http://${WINDOWS_HOST_IP}/api/webhooks/marketplace"

# Generate current Unix timestamp
TIMESTAMP=$(date +%s)
# The event ID must match the ID inside the JSON payload
EVENT_ID="evt_01JAZ8R2W4Y6A8C0E2G4J6L8N0"

# ==============================================================================
# JSON Payload (Order Shipped)
# ==============================================================================
PAYLOAD='{
    "id": "evt_01JAZ8R2W4Y6A8C0E2G4J6L8N0",
    "type": "order.shipped",
    "created_at": "2026-10-22T06:40:04+00:00",
    "data": {
        "order": {
            "id": "ord_7Q2KX9M4",
            "number": "MH-1048213",
            "status": "shipped",
            "currency": "RUB",
            "customer": {
                "email": "anna.smirnova@example.com",
                "name": "Анна Смирнова",
                "phone": "+79161234567"
            },
            "items": [
                {
                    "sku": "TSHIRT-BLK-M",
                    "name": "Футболка базовая, чёрная, M",
                    "quantity": 3,
                    "price": "1290.00",
                    "subtotal": "3870.00"
                },
                {
                    "sku": "CAP-NVY",
                    "name": "Бейсболка \"Classic\" / синяя",
                    "quantity": 1,
                    "price": "890.50",
                    "subtotal": "890.50"
                }
            ],
            "delivery_price": "349.00",
            "discount": "200.00",
            "total": "4909.50",
            "shipment": {
                "carrier": "СДЭК",
                "tracking_number": "1098765432",
                "tracking_url": "https://www.cdek.ru/ru/tracking?order_id=1098765432"
            },
            "paid_at": "2026-10-21T13:05:47+03:00",
            "cancelled_at": null,
            "cancel_reason": null,
            "created_at": "2026-10-21T13:02:11+03:00",
            "updated_at": "2026-10-22T06:40:03+00:00",
            "history": [
                {
                    "status": "created",
                    "at": "2026-10-21T10:05:48+03:00"
                },
                {
                    "status": "shipped",
                    "at": "2026-10-22T06:40:03+03:00"
                }
            ]
        }
    }
}'

# ==============================================================================
# Signature Generation & Request
# ==============================================================================
SIGNATURE="sha256=$(echo -n "$TIMESTAMP.$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')"

echo "📡 Sending shipped order webhook to: $URL"
echo "🕒 Timestamp: $TIMESTAMP"
echo "🆔 Event ID: $EVENT_ID"
echo "🔑 Signature: $SIGNATURE"
echo "--------------------------------------------------"

curl -i -X POST "$URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Timestamp: $TIMESTAMP" \
  -H "X-Webhook-Signature: $SIGNATURE" \
  -H "X-Webhook-Id: $EVENT_ID" \
  -d "$PAYLOAD"

echo ""
echo "--------------------------------------------------"
echo "✅ Request sent."