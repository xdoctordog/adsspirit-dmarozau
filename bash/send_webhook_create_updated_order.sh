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
EVENT_ID="evt_01JAXM0C3T5V7X9Z1B3D5F7H9K"

# ==============================================================================
# JSON Payload (Order Updated)
# ==============================================================================
PAYLOAD='{
    "id": "evt_01JAXM0C3T5V7X9Z1B3D5F7H9K",
    "type": "order.updated",
    "created_at": "2026-10-21T10:03:31+00:00",
    "data": {
        "order": {
            "id": "ord_7Q2KX9M4",
            "number": "MH-1048213",
            "status": "created",
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
            "shipment": null,
            "paid_at": null,
            "cancelled_at": null,
            "cancel_reason": null,
            "created_at": "2026-10-21T13:02:11+03:00",
            "updated_at": "2026-10-21T13:03:30+03:00",
            "history": [
                {
                    "status": "created",
                    "at": "2026-10-21T13:02:11+03:00"
                },
                {
                    "status": "created",
                    "at": "2026-10-21T13:03:30+03:00"
                }
            ]
        }
    }
}'

# ==============================================================================
# Signature Generation & Request
# ==============================================================================
SIGNATURE="sha256=$(echo -n "$TIMESTAMP.$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')"

echo "📡 Sending updated order webhook to: $URL"
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
