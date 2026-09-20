import assert from 'node:assert/strict'
import test from 'node:test'
import { salesToCsv } from '../src/lib/csv.ts'

test('gera CSV UTF-8 com separador compatível com Excel', () => {
  const csv = salesToCsv([{
    id: 1,
    external_id: 'manual-1',
    campaign_id: 2,
    campaign_name: 'Campanha; Setembro',
    seller_id: 3,
    seller_name: 'José "Jota"',
    product_id: 4,
    product_name: 'Café Gourmet',
    quantity: 2,
    unit_value: '149.90',
    points: 20,
    status: 'approved',
    created_at: '2026-09-19 12:00:00',
  }])

  assert.match(csv, /^\uFEFF"id";"external_id";/)
  assert.match(csv, /"Campanha; Setembro"/)
  assert.match(csv, /"José ""Jota"""/)
  assert.match(csv, /"Café Gourmet";"Campanha; Setembro";"2";"149\.90";"20";"Aprovada"/)
})
