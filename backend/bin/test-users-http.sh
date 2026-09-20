#!/bin/sh

set -eu

base_url="${BASE_URL:-http://localhost:8080}"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT
email="seller-$(date +%s)-$$@toro.local"

status() {
    curl -sS -o "$tmp_dir/body" -w '%{http_code}' "$@"
}

login() {
    email_value="$1"
    password="$2"
    output="$3"
    status_code="$(curl -sS -o "$output" -w '%{http_code}' \
        -X POST "$base_url/auth/login" \
        -H 'Content-Type: application/json' \
        --data "{\"email\":\"$email_value\",\"password\":\"$password\"}")"
    [ "$status_code" = 200 ]
    sed -n 's/.*"token":"\([^"]*\)".*/\1/p' "$output"
}

admin_token="$(login admin@toro.local admin123 "$tmp_dir/admin.json")"
seller_token="$(login seller1@toro.local seller123 "$tmp_dir/seller.json")"

[ "$(status "$base_url/users/sellers")" = 401 ]
[ "$(status "$base_url/users/sellers" -H "Authorization: Bearer $seller_token")" = 403 ]
[ "$(status "$base_url/users" -X POST -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data '{"name":"Invalid Seller","email":"invalid-seller@toro.local","password":"short"}')" = 422 ]

create_status="$(curl -sS -o "$tmp_dir/create.json" -w '%{http_code}' \
    -X POST "$base_url/users" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"HTTP Seller\",\"email\":\"$email\",\"password\":\"seller123\"}")"
[ "$create_status" = 201 ]
grep -q '"role":"seller"' "$tmp_dir/create.json"
! grep -q 'password_hash' "$tmp_dir/create.json"

[ "$(status "$base_url/users" -X POST -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"Duplicate Seller\",\"email\":\"$email\",\"password\":\"seller123\"}")" = 409 ]

[ "$(status "$base_url/users/sellers" -H "Authorization: Bearer $admin_token")" = 200 ]
grep -q "$email" "$tmp_dir/body"

printf '%s\n' 'HTTP user checks passed.'
