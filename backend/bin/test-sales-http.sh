#!/bin/sh

set -eu

base_url="${BASE_URL:-http://localhost:8080}"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT
suffix="$(date +%s)"
external_id="http-sale-$suffix"

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

payload="{\"external_id\":\"$external_id\",\"campaign_id\":1,\"seller_id\":2,\"product_id\":1,\"quantity\":3,\"unit_value\":\"149.90\"}"

assert_status 401 "$base_url/sales" -X POST \
    -H 'Content-Type: application/json' \
    --data "$payload"
assert_status 403 "$base_url/sales" -X POST \
    -H "Authorization: Bearer $seller_token" \
    -H 'Content-Type: application/json' \
    --data "$payload"
assert_status 422 "$base_url/sales" -X POST \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data '{"campaign_id":1,"seller_id":2,"product_id":1,"quantity":1,"unit_value":"10.00"}'

create_status="$(curl -sS -o "$tmp_dir/create.json" -w '%{http_code}' \
    -X POST "$base_url/sales" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "$payload")"
[ "$create_status" = 201 ]
grep -q '"status":"approved"' "$tmp_dir/create.json"
grep -q '"unit_value":"149.90"' "$tmp_dir/create.json"

assert_status 200 "$base_url/sales" -X POST \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "$payload"
assert_status 409 "$base_url/sales" -X POST \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"external_id\":\"$external_id\",\"campaign_id\":1,\"seller_id\":2,\"product_id\":1,\"quantity\":4,\"unit_value\":\"149.90\"}"
assert_status 422 "$base_url/sales" -X POST \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"external_id\":\"budget-$suffix\",\"campaign_id\":1,\"seller_id\":2,\"product_id\":1,\"quantity\":101,\"unit_value\":\"1.00\"}"

printf '%s\n' 'HTTP sales checks passed.'
