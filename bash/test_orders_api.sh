#!/bin/bash

# ==============================================================================
# Configuration
# ==============================================================================
# Automatically detect the Windows Host IP for WSL routing
WINDOWS_HOST_IP=$(ip route show default | awk '{print $3}')
BASE_URL="http://${WINDOWS_HOST_IP}/api/orders"

echo "🚀 Starting API Tests for: $BASE_URL"
echo "--------------------------------------------------"

# ==============================================================================
# Helper Function to execute and format requests
# ==============================================================================
run_request() {
    local title="$1"
    local url="$2"

    echo -e "\n🔍 TEST: $title"
    echo "👉 URL: $url"

    # Execute curl, capture status code and body
    response=$(curl -s -w "\n%{http_code}" "$url")

    # Split the response into body and status code
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')

    echo "📡 HTTP Status: $http_code"

    # Pretty print JSON if 'jq' is installed, otherwise print raw
    if command -v jq &> /dev/null; then
        echo "$body" | jq '.'
    else
        # echo "$body" | head -c 500 # Print first 500 chars if jq is missing
        # echo "... (truncated)"
        echo "$body"
    fi

    echo "--------------------------------------------------"
}

# ==============================================================================
# Test Cases
# ==============================================================================

# 1. Base Endpoint (Default pagination: page=1, per_page=20)
run_request "Base List (Default Pagination)" "$BASE_URL"

# 2. Filter by Status (Valid Enum)
run_request "Filter by Status (valid: paid)" "$BASE_URL?status=paid"

# 3. Filter by Status (Another valid Enum)
run_request "Filter by Status (valid: shipped)" "$BASE_URL?status=shipped"

# 4. Filter by Status (Invalid Enum -> Should return 422)
run_request "Filter by Status (invalid -> 422)" "$BASE_URL?status=unknown_status"

# 5. Filter by Email (Note: Replace with a real email from your DB to see data)
run_request "Filter by Email (case-insensitive)" "$BASE_URL?email=vschulist@example.com"

# 6.A Pagination (Custom per_page and page)
run_request "Pagination (per_page=5, page=2)" "$BASE_URL?per_page=7&page=1"

# 6.B Pagination (Custom per_page and page)
run_request "Pagination (per_page=5, page=2)" "$BASE_URL?per_page=5&page=2"

# 7. Pagination Limit Cap (Max 100 per_page)
run_request "Pagination Limit Cap (per_page=500)" "$BASE_URL?per_page=500"

# 8. Fetch Single Order
# First, let's try to extract a real marketplace_id from the first request using PHP (since jq might be missing)
echo -e "\n🔍 Extracting a real Order ID for the next test..."
ORDER_ID=$(curl -s "$BASE_URL" | php -r "echo json_decode(file_get_contents('php://stdin'), true)['data'][0]['id'] ?? '';")

if [ -n "$ORDER_ID" ]; then
    run_request "Get Single Order by ID" "$BASE_URL/$ORDER_ID"
else
    echo "⚠️ Skipping Single Order test: Database is empty or no orders found."
    echo "--------------------------------------------------"
fi

# 9.A Non-existent Order (Should return 404)
run_request "Get Non-existent Order (404)" "$BASE_URL/ord_nonexistent_123456"

# 9.B Non-existent Order (Should return 404)
run_request "Get Non-existent Order (404)" "$BASE_URL/9999999"
