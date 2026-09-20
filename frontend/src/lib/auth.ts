import type { Role } from '../types'

const TOKEN_KEY = 'toro.token'

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function saveToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token)
}

export function clearToken(): void {
  localStorage.removeItem(TOKEN_KEY)
}

export function getRole(token: string): Role | null {
  try {
    const [, payload] = token.split('.')
    if (!payload) return null

    const normalized = payload.replace(/-/g, '+').replace(/_/g, '/')
    const decoded = JSON.parse(atob(normalized.padEnd(Math.ceil(normalized.length / 4) * 4, '='))) as {
      role?: unknown
    }

    return decoded.role === 'admin' || decoded.role === 'seller' ? decoded.role : null
  } catch {
    return null
  }
}
