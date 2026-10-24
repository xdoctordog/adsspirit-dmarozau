#!/bin/bash

# ==============================================================================
# Configuration (Make sure SECRET matches your .env file)
#
# ! CHANGE URL !
# ip route show default | awk '{print $3}'
# 172.25.112.1
#
# ==============================================================================
SECRET="paste_the_secret_from_MarketHub_dashboard_here"
WINDOWS_HOST_IP=$(ip route show default | awk '{print $3}')
URL="http://${WINDOWS_HOST_IP}/api/webhooks/marketplace"

# Generate current Unix timestamp
TIMESTAMP=$(date +%s)
# The event ID must match the ID inside the JSON payload
EVENT_ID="evt_01JAXKZB7M2D9F4H6J8K0N3P5R"

# ==============================================================================
# JSON Payload (Exact copy of the provided payload)
# ==============================================================================
PAYLOAD='{
    "id": "evt_01JAXKZB7M2D9F4H6J8K0N3P5R",
    "type": "order.created",
    "created_at": "2026-10-21T10:02:12+00:00",
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
                    "quantity": 2,
                    "price": "1290.00",
                    "subtotal": "2580.00"
                }
            ],
            "delivery_price": "500.00",
            "discount": "0.00",
            "total": "2580.00",
            "shipment": null,
            "paid_at": null,
            "cancelled_at": null,
            "cancel_reason": null,
            "created_at": "2026-10-21T10:04:15+00:00",
            "updated_at": "2026-10-21T11:05:25+00:00",
            "history": [
                {
                    "status": "created",
                    "at": "2026-10-21T10:04:15+00:00"
                }
            ]
        }
    }
}'

# ==============================================================================
# Signature Generation
# ==============================================================================
SIGNATURE="sha256=$(echo -n "$TIMESTAMP.$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')"

echo "📡 Sending webhook..."
echo "🕒 Timestamp: $TIMESTAMP"
echo "🔑 Signature: $SIGNATURE"
echo "--------------------------------------------------"

# ==============================================================================
# Send Request
# ==============================================================================
curl -i -X POST "$URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Timestamp: $TIMESTAMP" \
  -H "X-Webhook-Signature: $SIGNATURE" \
  -H "X-Webhook-Id: $EVENT_ID" \
  -d "$PAYLOAD"

echo ""
echo "--------------------------------------------------"
echo "✅ Request sent."
