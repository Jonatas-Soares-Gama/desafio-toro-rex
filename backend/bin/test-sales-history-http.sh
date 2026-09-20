#!/bin/sh

set -eu

base_url="${BASE_URL:-http://localhost:8080}"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT
external_id="history-sale-$(date +%s)-$$"

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
payload="{\"external_id\":\"$external_id\",\"campaign_id\":1,\"seller_id\":2,\"product_id\":1,\"quantity\":1,\"unit_value\":\"1.00\"}"

assert_status 401 "$base_url/sales"
assert_status 403 "$base_url/sales" -H "Authorization: Bearer $seller_token"

assert_status 201 "$base_url/sales" -X POST \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "$payload"

assert_status 200 "$base_url/sales" -H "Authorization: Bearer $admin_token"
grep -q "$external_id" "$tmp_dir/body"
grep -q '"seller_name"' "$tmp_dir/body"
grep -q '"product_name"' "$tmp_dir/body"
grep -q '"campaign_name"' "$tmp_dir/body"
grep -q '"points":' "$tmp_dir/body"

assert_status 200 "$base_url/sales/$external_id/cancel" -X POST \
    -H "Authorization: Bearer $admin_token"
grep -q '"status":"canceled"' "$tmp_dir/body"

printf '%s\n' 'HTTP sales history checks passed.'
