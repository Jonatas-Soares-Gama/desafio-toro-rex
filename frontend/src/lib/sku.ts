export function skuFromName(name: string, existingSkus: string[] = []): string {
  const base = name
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toUpperCase()
    .replace(/[^A-Z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 80)
    .replace(/-+$/g, '')

  if (!base) return ''

  const used = new Set(existingSkus)
  if (!used.has(base)) return base

  let suffix = 2
  while (used.has(`${base.slice(0, 80 - String(suffix).length - 1)}-${suffix}`)) suffix += 1
  return `${base.slice(0, 80 - String(suffix).length - 1)}-${suffix}`
}
