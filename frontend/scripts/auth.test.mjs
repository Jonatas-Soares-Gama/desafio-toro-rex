import assert from 'node:assert/strict'
import { Buffer } from 'node:buffer'
import { test } from 'node:test'
import { getRole } from '../src/lib/auth.ts'

function tokenFor(role) {
  const payload = Buffer.from(JSON.stringify({ role })).toString('base64url')
  return `header.${payload}.signature`
}

test('lê apenas papéis aceitos do payload do JWT', () => {
  assert.equal(getRole(tokenFor('admin')), 'admin')
  assert.equal(getRole(tokenFor('seller')), 'seller')
  assert.equal(getRole(tokenFor('root')), null)
  assert.equal(getRole('not-a-token'), null)
})
