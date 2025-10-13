#!/bin/bash

echo "=== TESTING ITSO HELPDESK API ENDPOINTS ==="
echo ""

BASE_URL="http://127.0.0.1:8001"

echo "1. Testing GET /api/v1/tickets (List all tickets)"
curl -X GET "$BASE_URL/api/v1/tickets" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" | jq '.'

echo ""
echo "2. Testing POST /api/v1/webhooks/email (Email webhook)"
curl -X POST "$BASE_URL/api/v1/webhooks/email" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "from_email": "customer@company.com",
    "from_name": "John Customer",
    "subject": "System Login Issue",
    "body": "I cannot login to the system. Getting error message.",
    "message_id": "email_'$(date +%s)'"
  }' | jq '.'

echo ""
echo "3. Testing POST /api/v1/webhooks/whatsapp (WhatsApp webhook)"
curl -X POST "$BASE_URL/api/v1/webhooks/whatsapp" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "from_number": "+6281234567890",
    "from_name": "WhatsApp Customer",
    "message": "Halo, saya butuh bantuan untuk reset password",
    "message_id": "wa_'$(date +%s)'"
  }' | jq '.'

echo ""
echo "4. Testing GET /api/v1/tickets (Check if new tickets created)"
curl -X GET "$BASE_URL/api/v1/tickets" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" | jq '.'

echo ""
echo "=== API TESTING COMPLETE ==="