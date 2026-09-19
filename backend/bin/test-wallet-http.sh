#!/bin/sh

set -eu

base_url="${BASE_URL:-http://localhost:8080}"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT
suffix="$(date +%s)"
product_sku="WALLET-$suffix"
campaign_name="Wallet HTTP $suffix"
external_id="wallet-sale-$suffix"

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

wallet_balance() {
    token="$1"
    output="$2"
    status_code="$(curl -sS -o "$output" -w '%{http_code}' \
        "$base_url/me/wallet" \
        -H "Authorization: Bearer $token")"
    [ "$status_code" = 200 ]
    sed -n 's/.*"balance":\(-*[0-9]*\).*/\1/p' "$output"
}

admin_token="$(login admin@toro.local admin123 "$tmp_dir/admin.json")"
seller_token="$(login seller1@toro.local seller123 "$tmp_dir/seller.json")"
other_seller_token="$(login seller2@toro.local seller123 "$tmp_dir/other-seller.json")"

assert_status 401 "$base_url/me/wallet"
assert_status 403 "$base_url/me/wallet" -H "Authorization: Bearer $admin_token"

product_status="$(curl -sS -o "$tmp_dir/product.json" -w '%{http_code}' \
    -X POST "$base_url/products" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"Wallet Product $suffix\",\"sku\":\"$product_sku\",\"points_per_unit\":100}")"
[ "$product_status" = 201 ]
product_id="$(sed -n 's/.*"id":\([0-9]*\).*/\1/p' "$tmp_dir/product.json")"

starts_at="$(date -u '+%Y-%m-%d %H:%M:%S')"
ends_at="$(date -u -d '+30 days' '+%Y-%m-%d %H:%M:%S')"
campaign_status="$(curl -sS -o "$tmp_dir/campaign.json" -w '%{http_code}' \
    -X POST "$base_url/campaigns" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"name\":\"$campaign_name\",\"budget_total\":10000,\"starts_at\":\"$starts_at\",\"ends_at\":\"$ends_at\"}")"
[ "$campaign_status" = 201 ]
campaign_id="$(sed -n 's/.*"id":\([0-9]*\).*/\1/p' "$tmp_dir/campaign.json")"

before_balance="$(wallet_balance "$seller_token" "$tmp_dir/before.json")"

sale_status="$(curl -sS -o "$tmp_dir/sale.json" -w '%{http_code}' \
    -X POST "$base_url/sales" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"external_id\":\"$external_id\",\"campaign_id\":$campaign_id,\"seller_id\":2,\"product_id\":$product_id,\"quantity\":1,\"unit_value\":\"10.00\"}")"
[ "$sale_status" = 201 ]

credit_balance="$(wallet_balance "$seller_token" "$tmp_dir/credit.json")"
[ "$credit_balance" -eq $((before_balance + 100)) ]
grep -q "\"type\":\"credit\"" "$tmp_dir/credit.json"
grep -q "$external_id" "$tmp_dir/credit.json"

seller2_status="$(curl -sS -o "$tmp_dir/other-wallet.json" -w '%{http_code}' \
    "$base_url/me/wallet?seller_id=3" \
    -H "Authorization: Bearer $other_seller_token")"
[ "$seller2_status" = 200 ]
if grep -q "$external_id" "$tmp_dir/other-wallet.json"; then
    printf '%s\n' 'Wallet leaked entries from another seller.' >&2
    exit 1
fi

cancel_status="$(curl -sS -o "$tmp_dir/cancel.json" -w '%{http_code}' \
    -X POST "$base_url/sales/$external_id/cancel" \
    -H "Authorization: Bearer $admin_token")"
[ "$cancel_status" = 200 ]

repeat_status="$(curl -sS -o "$tmp_dir/repeat.json" -w '%{http_code}' \
    -X POST "$base_url/sales/$external_id/cancel" \
    -H "Authorization: Bearer $admin_token")"
[ "$repeat_status" = 200 ]

debit_balance="$(wallet_balance "$seller_token" "$tmp_dir/debit.json")"
[ "$debit_balance" -eq "$before_balance" ]
grep -q "\"type\":\"debit\"" "$tmp_dir/debit.json"
[ "$(grep -o "$external_id" "$tmp_dir/debit.json" | wc -l)" -eq 2 ]

printf '%s\n' 'HTTP wallet checks passed.'
