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
EVENT_ID="evt_01JAXZ6E8G0J2L4N6Q8S0U2W4Y"

# ==============================================================================
# JSON Payload (Order Cancelled)
# ==============================================================================
PAYLOAD='{
    "id": "evt_01JAXZ6E8G0J2L4N6Q8S0U2W4Y",
    "type": "order.cancelled",
    "created_at": "2026-10-21T17:47:16+00:00",
    "data": {
        "order": {
            "id": "ord_3HV8PW2C",
            "number": "MH-1048377",
            "status": "cancelled",
            "currency": "RUB",
            "customer": {
                "email": "i.lebedev@example.org",
                "name": "Игорь Лебедев",
                "phone": null
            },
            "items": [
                {
                    "sku": "MUG-WHT-350",
                    "name": "Кружка керамическая, белая, 350 мл",
                    "quantity": 2,
                    "price": "649.00",
                    "subtotal": "1298.00"
                }
            ],
            "delivery_price": "0.00",
            "discount": "0.00",
            "total": "1298.00",
            "shipment": null,
            "paid_at": "2026-10-21T18:11:02+03:00",
            "cancelled_at": "2026-10-21T20:47:15+03:00",
            "cancel_reason": "Покупатель отказался от заказа",
            "created_at": "2026-10-21T18:09:40+03:00",
            "updated_at": "2026-10-21T20:47:15+03:00",
            "history": [
                {
                    "status": "created",
                    "at": "2026-10-21T17:47:16+00:00"
                },
                {
                    "status": "paid",
                    "at": "2026-10-21T17:49:16+00:00"
                },
                {
                    "status": "cancelled",
                    "at": "2026-10-21T17:55:16+00:00"
                }
            ]
        }
    }
}'

# ==============================================================================
# Signature Generation & Request
# ==============================================================================
SIGNATURE="sha256=$(echo -n "$TIMESTAMP.$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')"

echo "📡 Sending cancelled order webhook to: $URL"
echo "🕒 Timestamp: $TIMESTAMP"
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