#!/bin/bash
# API Endpoint Diagnostic Script
# Tests all API endpoints to identify which ones exist and which return 404

echo "======================================"
echo "SIDIKSAE API Endpoint Diagnostic Test"
echo "Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo "======================================"
echo ""

API_BASE="https://api.sidiksae.my.id"
API_KEY="sk_live_suratqu_surat2026"
APP_ID="suratqu"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test function
test_endpoint() {
    local method=$1
    local endpoint=$2
    local headers=$3
    local data=$4
    local desc=$5
    
    echo "----------------------------------------"
    echo "TEST: $desc"
    echo "URL: $API_BASE$endpoint"
    echo "METHOD: $method"
    
    if [ -z "$data" ]; then
        response=$(curl -s -w "\n%{http_code}" -X $method "$API_BASE$endpoint" $headers)
    else
        response=$(curl -s -w "\n%{http_code}" -X $method "$API_BASE$endpoint" $headers -d "$data")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)
    
    echo "HTTP CODE: $http_code"
    
    if [ "$http_code" = "200" ] || [ "$http_code" = "201" ]; then
        echo -e "${GREEN}✅ SUCCESS${NC}"
    elif [ "$http_code" = "400" ] || [ "$http_code" = "401" ]; then
        echo -e "${YELLOW}⚠️  ENDPOINT EXISTS (Auth/Validation Error)${NC}"
    elif [ "$http_code" = "404" ]; then
        echo -e "${RED}❌ NOT FOUND${NC}"
    else
        echo -e "${YELLOW}? HTTP $http_code${NC}"
    fi
    
    echo "RESPONSE:"
    echo "$body" | jq '.' 2>/dev/null || echo "$body"
    echo ""
}

# Test 1: Root endpoint
test_endpoint "GET" "/" "" "" "Root Endpoint"

# Test 2: Health check (no /api/v1 prefix)
test_endpoint "GET" "/health" "" "" "Health Check (Root)"

# Test 3: Health check (with /api/v1 prefix)
test_endpoint "GET" "/api/v1/health" "" "" "Health Check (/api/v1)"

# Test 4: Auth endpoint WITHOUT headers
test_endpoint "POST" "/api/v1/auth/token" \
    "-H 'Content-Type: application/json'" \
    '{}' \
    "Auth Endpoint (No Headers)"

# Test 5: Auth endpoint WITH headers
test_endpoint "POST" "/api/v1/auth/token" \
    "-H 'Content-Type: application/json' -H 'X-API-KEY: $API_KEY' -H 'X-APP-ID: $APP_ID'" \
    '{}' \
    "Auth Endpoint (With Headers)"

# Test 6: Disposisi push WITHOUT auth
test_endpoint "POST" "/api/v1/disposisi/push" \
    "-H 'Content-Type: application/json' -H 'X-API-KEY: $API_KEY' -H 'X-APP-ID: $APP_ID'" \
    '{
        "nomor_agenda": "TEST/001/2026",
        "nomor_surat": "SM/TEST/2026",
        "perihal": "Test Diagnostic",
        "asal_surat": "SuratQu Test",
        "tanggal_surat": "2026-01-05"
    }' \
    "Disposisi Push (No JWT)"

# Test 7: Disposisi create (alias)
test_endpoint "POST" "/api/v1/disposisi/create" \
    "-H 'Content-Type: application/json' -H 'X-API-KEY: $API_KEY' -H 'X-APP-ID: $APP_ID'" \
    '{
        "nomor_agenda": "TEST/001/2026",
        "nomor_surat": "SM/TEST/2026",
        "perihal": "Test Diagnostic",
        "asal_surat": "SuratQu Test",
        "tanggal_surat": "2026-01-05"
    }' \
    "Disposisi Create (Alias)"

# Test 8: List all possible endpoint patterns
echo "========================================"
echo "TESTING COMMON ENDPOINT PATTERNS:"
echo "========================================"
echo ""

for path in "/api/v1/disposisi" "/disposisi/push" "/v1/disposisi/push" "/api/disposisi/push"; do
    test_endpoint "GET" "$path" "-H 'X-API-KEY: $API_KEY'" "" "GET $path"
done

echo ""
echo "======================================"
echo "DIAGNOSTIC COMPLETE"
echo "======================================"
echo ""
echo "SUMMARY:"
echo "- If you see ✅ SUCCESS: Endpoint works perfectly"
echo "- If you see ⚠️  ENDPOINT EXISTS: Endpoint exists but needs proper auth/data"
echo "- If you see ❌ NOT FOUND: Endpoint doesn't exist - API route not defined"
echo ""
echo "NEXT STEPS:"
echo "1. Review which endpoints return 200/201/400/401 (these exist!)"
echo "2. Check which endpoints return 404 (these DON'T exist)"
echo "3. Update SuratQu configuration to use only existing endpoints"
echo ""
