#!/bin/sh

set -eu

base_url="${BASE_URL:-http://localhost:8080}"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

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

assert_status 200 "$base_url/health"
assert_status 401 "$base_url/admin/ping"

admin_status="$(curl -sS -o "$tmp_dir/admin.json" -w '%{http_code}' \
    -X POST "$base_url/auth/login" \
    -H 'Content-Type: application/json' \
    --data '{"email":"admin@toro.local","password":"admin123"}')"
[ "$admin_status" = 200 ]
admin_token="$(sed -n 's/.*"token":"\([^"]*\)".*/\1/p' "$tmp_dir/admin.json")"
[ -n "$admin_token" ]

seller_status="$(curl -sS -o "$tmp_dir/seller.json" -w '%{http_code}' \
    -X POST "$base_url/auth/login" \
    -H 'Content-Type: application/json' \
    --data '{"email":"seller1@toro.local","password":"seller123"}')"
[ "$seller_status" = 200 ]
seller_token="$(sed -n 's/.*"token":"\([^"]*\)".*/\1/p' "$tmp_dir/seller.json")"
[ -n "$seller_token" ]

assert_status 403 "$base_url/admin/ping" -H "Authorization: Bearer $seller_token"
assert_status 200 "$base_url/admin/ping" -H "Authorization: Bearer $admin_token"

printf '%s\n' 'HTTP authorization checks passed.'
