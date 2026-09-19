#!/bin/sh

set -eu

base_url="${BASE_URL:-http://localhost:8080}"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT
suffix="$(date +%s)"

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
    --data "{\"name\":\"Concurrency Product $suffix\",\"sku\":\"CONC-$suffix\",\"points_per_unit\":100}")"
[ "$product_status" = 201 ]
product_id="$(sed -n 's/.*"id":\([0-9]*\).*/\1/p' "$tmp_dir/product.json")"

starts_at="$(date -u '+%Y-%m-%d %H:%M:%S')"
ends_at="$(date -u -d '+30 days' '+%Y-%m-%d %H:%M:%S')"

create_campaign() {
    name="$1"
    output="$2"
    status_code="$(curl -sS -o "$output" -w '%{http_code}' \
        -X POST "$base_url/campaigns" \
        -H "Authorization: Bearer $admin_token" \
        -H 'Content-Type: application/json' \
        --data "{\"name\":\"$name\",\"budget_total\":100,\"starts_at\":\"$starts_at\",\"ends_at\":\"$ends_at\"}")"
    [ "$status_code" = 201 ]
    sed -n 's/.*"id":\([0-9]*\).*/\1/p' "$output"
}

campaign_id="$(create_campaign "Concurrency Sales $suffix" "$tmp_dir/sales-campaign.json")"
sale_a="concurrent-sale-a-$suffix"
sale_b="concurrent-sale-b-$suffix"

curl -sS -o "$tmp_dir/sale-a.json" -w '%{http_code}' \
    -X POST "$base_url/sales" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"external_id\":\"$sale_a\",\"campaign_id\":$campaign_id,\"seller_id\":2,\"product_id\":$product_id,\"quantity\":1,\"unit_value\":\"10.00\"}" \
    > "$tmp_dir/sale-a.status" &
sale_a_pid=$!

curl -sS -o "$tmp_dir/sale-b.json" -w '%{http_code}' \
    -X POST "$base_url/sales" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"external_id\":\"$sale_b\",\"campaign_id\":$campaign_id,\"seller_id\":2,\"product_id\":$product_id,\"quantity\":1,\"unit_value\":\"10.00\"}" \
    > "$tmp_dir/sale-b.status" &
sale_b_pid=$!

wait "$sale_a_pid"
wait "$sale_b_pid"
sale_a_status="$(cat "$tmp_dir/sale-a.status")"
sale_b_status="$(cat "$tmp_dir/sale-b.status")"

if ! { [ "$sale_a_status" = 201 ] && [ "$sale_b_status" = 422 ]; } \
    && ! { [ "$sale_a_status" = 422 ] && [ "$sale_b_status" = 201 ]; }; then
    printf 'Expected concurrent sale statuses 201 and 422, got %s and %s.\n' \
        "$sale_a_status" "$sale_b_status" >&2
    cat "$tmp_dir/sale-a.json" "$tmp_dir/sale-b.json" >&2
    exit 1
fi

campaigns_json="$tmp_dir/campaigns.json"
curl -sS -o "$campaigns_json" \
    "$base_url/campaigns" \
    -H "Authorization: Bearer $admin_token"
sales_budget_used="$(sed -n "s/.*\"name\":\"Concurrency Sales $suffix\",\"budget_total\":100,\"budget_used\":\([0-9]*\).*/\1/p" "$campaigns_json")"
[ "$sales_budget_used" = 100 ]

wallet_after_sales="$tmp_dir/wallet-after-sales.json"
curl -sS -o "$wallet_after_sales" \
    "$base_url/me/wallet" \
    -H "Authorization: Bearer $seller_token"
[ "$(grep -o "$sale_a\|$sale_b" "$wallet_after_sales" | wc -l)" -eq 1 ]

cancel_campaign_id="$(create_campaign "Concurrency Cancel $suffix" "$tmp_dir/cancel-campaign.json")"
cancel_sale="concurrent-cancel-$suffix"
create_cancel_status="$(curl -sS -o "$tmp_dir/cancel-sale.json" -w '%{http_code}' \
    -X POST "$base_url/sales" \
    -H "Authorization: Bearer $admin_token" \
    -H 'Content-Type: application/json' \
    --data "{\"external_id\":\"$cancel_sale\",\"campaign_id\":$cancel_campaign_id,\"seller_id\":2,\"product_id\":$product_id,\"quantity\":1,\"unit_value\":\"10.00\"}")"
[ "$create_cancel_status" = 201 ]

curl -sS -o "$tmp_dir/cancel-a.json" -w '%{http_code}' \
    -X POST "$base_url/sales/$cancel_sale/cancel" \
    -H "Authorization: Bearer $admin_token" \
    > "$tmp_dir/cancel-a.status" &
cancel_a_pid=$!

curl -sS -o "$tmp_dir/cancel-b.json" -w '%{http_code}' \
    -X POST "$base_url/sales/$cancel_sale/cancel" \
    -H "Authorization: Bearer $admin_token" \
    > "$tmp_dir/cancel-b.status" &
cancel_b_pid=$!

wait "$cancel_a_pid"
wait "$cancel_b_pid"
[ "$(cat "$tmp_dir/cancel-a.status")" = 200 ]
[ "$(cat "$tmp_dir/cancel-b.status")" = 200 ]

curl -sS -o "$campaigns_json" \
    "$base_url/campaigns" \
    -H "Authorization: Bearer $admin_token"
cancel_budget_used="$(sed -n "s/.*\"name\":\"Concurrency Cancel $suffix\",\"budget_total\":100,\"budget_used\":\([0-9]*\).*/\1/p" "$campaigns_json")"
[ "$cancel_budget_used" = 0 ]

curl -sS -o "$tmp_dir/wallet-after-cancel.json" \
    "$base_url/me/wallet" \
    -H "Authorization: Bearer $seller_token"
[ "$(grep -o "$cancel_sale" "$tmp_dir/wallet-after-cancel.json" | wc -l)" -eq 2 ]

printf '%s\n' 'HTTP concurrency checks passed.'
