#!/bin/sh

set -eu

base_url="${BASE_URL:-http://localhost:8080}"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT
external_id="history-sale-$(date +%s)-$$"
product_sku="HISTORY-$(date +%s)-$$"
campaign_name="History Campaign $(date +%s)-$$"

status() {
    curl -sS -o "$tmp_dir/body" -w '%{http_code}' "$@"
}

assert_status() {
    expected="$1"
    shift
    actual="$(status "$@")"

    if [ "$actual" != "$expected" ]; then
        printf 'Expected HTTP %s, got %s: ' "$expected" "$actual" >&2
        cat "$tmp_dir/body" >&2
        exit 1
    fi
}

login() {
    email="$1"
    password="$2"
    output="$3"
    status_code="$(curl -sS -o "$output" -w '%{http_code}' \
        -X POST "$base_url/auth/login" \
        -H 'Content-Type: application/json' \
        --data "{\"email\":\"$email\",\"password\":\"$password\"}")"
    [ "$status_code" = 200 ]
    sed -n 's/.*"token":"\([^\"]*\)".*/\1/p' "$output"
}

admin_token="$(login admin@toro.local admin123 "$tmp_dir/admin.json")"
seller_token="$(login seller1@toro.local seller123 "$tmp_dir/seller.json")"

product_status="$(curl -sS -o "$tmp_dir/product.json" -w '%{http_code}' \
    -X POST "$base_url/products" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"History Product\",\"sku\":\"$product_sku\",\"points_per_unit\":100}")"
[ "$product_status" = 201 ]
product_id="$(sed -n 's/.*"id":\([0-9]*\).*/\1/p' "$tmp_dir/product.json")"

starts_at="$(date -u '+%Y-%m-%d %H:%M:%S')"
ends_at="$(date -u -d '+30 days' '+%Y-%m-%d %H:%M:%S')"
campaign_status="$(curl -sS -o "$tmp_dir/campaign.json" -w '%{http_code}' \
    -X POST "$base_url/campaigns" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"$campaign_name\",\"budget_total\":1000,\"starts_at\":\"$starts_at\",\"ends_at\":\"$ends_at\"}")"
[ "$campaign_status" = 201 ]
campaign_id="$(sed -n 's/.*"id":\([0-9]*\).*/\1/p' "$tmp_dir/campaign.json")"
payload="{\"external_id\":\"$external_id\",\"campaign_id\":$campaign_id,\"seller_id\":2,\"product_id\":$product_id,\"quantity\":1,\"unit_value\":\"1.00\"}"

assert_status 401 "$base_url/sales"
assert_status 403 "$base_url/sales" -H "Authorization: Bearer $seller_token"

assert_status 201 "$base_url/sales" -X POST \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "$payload"

update_status="$(curl -sS -o "$tmp_dir/updated-product.json" -w '%{http_code}' \
    -X PUT "$base_url/products/$product_id" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"History Product\",\"sku\":\"$product_sku\",\"points_per_unit\":250}")"
[ "$update_status" = 200 ]

assert_status 200 "$base_url/sales" -H "Authorization: Bearer $admin_token"
grep -q "$external_id" "$tmp_dir/body"
grep -q '"seller_name"' "$tmp_dir/body"
grep -q '"product_name"' "$tmp_dir/body"
grep -q '"campaign_name"' "$tmp_dir/body"
grep -q "\"external_id\":\"$external_id\".*\"points\":100" "$tmp_dir/body"

assert_status 200 "$base_url/sales/$external_id/cancel" -X POST \
    -H "Authorization: Bearer $admin_token"
grep -q '"status":"canceled"' "$tmp_dir/body"
grep -q '"reversed_points":100' "$tmp_dir/body"

assert_status 200 "$base_url/campaigns" -H "Authorization: Bearer $admin_token"
grep -q "\"name\":\"$campaign_name\".*\"budget_used\":0" "$tmp_dir/body"

printf '%s\n' 'HTTP sales history checks passed.'
