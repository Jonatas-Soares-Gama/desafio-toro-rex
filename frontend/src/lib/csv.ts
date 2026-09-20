import type { SaleListItem } from '../types'

function escapeCsv(value: string | number): string {
  return `"${String(value).replaceAll('"', '""')}"`
}

export function salesToCsv(sales: SaleListItem[]): string {
  const header = ['id', 'external_id', 'seller', 'produto', 'campanha', 'quantidade', 'valor_unitario', 'pontos', 'status', 'criado_em']
  const rows = sales.map((sale) => [
    sale.id,
    sale.external_id,
    sale.seller_name,
    sale.product_name,
    sale.campaign_name,
    sale.quantity,
    sale.unit_value,
    sale.points,
    sale.status === 'approved' ? 'Aprovada' : 'Cancelada',
    sale.created_at,
  ])

  return `\uFEFF${[header, ...rows].map((row) => row.map(escapeCsv).join(';')).join('\r\n')}\r\n`
}

export function downloadCsv(filename: string, content: string): void {
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.click()
  URL.revokeObjectURL(url)
}
