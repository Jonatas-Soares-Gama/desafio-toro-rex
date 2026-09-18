#!/bin/sh

set -eu

base_url="${BASE_URL:-http://localhost:8080}"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT
suffix="$(date +%s)"

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

assert_status 401 "$base_url/campaigns"
assert_status 403 "$base_url/campaigns" -H "Authorization: Bearer $seller_token"
assert_status 403 "$base_url/campaigns" -X POST \
    -H "Authorization: Bearer $seller_token" \
    -H 'Content-Type: application/json' \
    --data '{"name":"Seller campaign","budget_total":1000,"starts_at":"2026-10-01 00:00:00","ends_at":"2026-10-31 23:59:59"}'
assert_status 422 "$base_url/campaigns" -X POST \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"Invalid campaign $suffix\",\"budget_total\":0,\"starts_at\":\"2026-10-31 23:59:59\",\"ends_at\":\"2026-10-01 00:00:00\"}"
assert_status 422 "$base_url/campaigns" -X POST \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"Managed fields $suffix\",\"budget_total\":1000,\"budget_used\":100,\"starts_at\":\"2026-10-01 00:00:00\",\"ends_at\":\"2026-10-31 23:59:59\"}"

create_status="$(curl -sS -o "$tmp_dir/create.json" -w '%{http_code}' \
    -X POST "$base_url/campaigns" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"HTTP Campaign $suffix\",\"budget_total\":5000,\"starts_at\":\"2026-10-01 00:00:00\",\"ends_at\":\"2026-10-31 23:59:59\"}")"
[ "$create_status" = 201 ]
grep -q '"budget_used":0' "$tmp_dir/create.json"
grep -q '"status":"active"' "$tmp_dir/create.json"

assert_status 200 "$base_url/campaigns" -H "Authorization: Bearer $admin_token"
grep -q "HTTP Campaign $suffix" "$tmp_dir/body"

printf '%s\n' 'HTTP campaign checks passed.'
