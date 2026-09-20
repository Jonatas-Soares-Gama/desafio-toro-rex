import assert from 'node:assert/strict'
import test from 'node:test'
import { skuFromName } from '../src/lib/sku.ts'

test('normalizes the product name into a SKU', () => {
  assert.equal(skuFromName('Café Gourmet 500g'), 'CAFE-GOURMET-500G')
})

test('adds a suffix when the generated SKU already exists', () => {
  assert.equal(skuFromName('Café Gourmet 500g', ['CAFE-GOURMET-500G']), 'CAFE-GOURMET-500G-2')
})

test('returns an empty SKU for names without usable characters', () => {
  assert.equal(skuFromName('---'), '')
})
