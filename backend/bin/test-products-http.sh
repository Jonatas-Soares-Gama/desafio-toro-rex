#!/bin/sh

set -eu

base_url="${BASE_URL:-http://localhost:8080}"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT
sku="HTTP-TEST-$(date +%s)"

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
    sed -n 's/.*"token":"\([^"]*\)".*/\1/p' "$output"
}

admin_token="$(login admin@toro.local admin123 "$tmp_dir/admin.json")"
seller_token="$(login seller1@toro.local seller123 "$tmp_dir/seller.json")"

assert_status 401 "$base_url/products"
assert_status 403 "$base_url/products" -H "Authorization: Bearer $seller_token"
assert_status 403 "$base_url/products" -H "Authorization: Bearer $seller_token" \
    -X POST -H 'Content-Type: application/json' \
    --data '{"name":"Seller product","sku":"SELLER-HTTP-TEST","points_per_unit":10}'
assert_status 422 "$base_url/products" -H "Authorization: Bearer $admin_token" \
    -X POST -H 'Content-Type: application/json' \
    --data '{"name":"Invalid product","sku":"INVALID-HTTP-TEST","points_per_unit":0}'

create_status="$(curl -sS -o "$tmp_dir/create.json" -w '%{http_code}' \
    -X POST "$base_url/products" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"HTTP Product\",\"sku\":\"$sku\",\"points_per_unit\":75}")"
[ "$create_status" = 201 ]
product_id="$(sed -n 's/.*"id":\([0-9]*\).*/\1/p' "$tmp_dir/create.json")"
[ -n "$product_id" ]

assert_status 409 "$base_url/products" -X POST \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"Duplicate HTTP Product\",\"sku\":\"$sku\",\"points_per_unit\":75}"

assert_status 200 "$base_url/products" -H "Authorization: Bearer $admin_token"
grep -q "\"sku\":\"$sku\"" "$tmp_dir/body"

assert_status 200 "$base_url/products/$product_id" -X PUT \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"Updated HTTP Product\",\"sku\":\"$sku\",\"points_per_unit\":80}"

assert_status 200 "$base_url/products/$product_id" -X DELETE \
    -H "Authorization: Bearer $admin_token"
grep -q '"active":false' "$tmp_dir/body"

assert_status 200 "$base_url/products" -H "Authorization: Bearer $admin_token"
grep -q "\"sku\":\"$sku\"" "$tmp_dir/body"
grep -q '"active":false' "$tmp_dir/body"

assert_status 200 "$base_url/products/$product_id" -X DELETE \
    -H "Authorization: Bearer $admin_token"
grep -q '"active":false' "$tmp_dir/body"

printf '%s\n' 'HTTP product CRUD checks passed.'
