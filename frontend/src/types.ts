export type Role = 'admin' | 'seller'

export type Product = {
  id: number
  name: string
  sku: string
  points_per_unit: number
  active: boolean
  created_at: string
}

export type Campaign = {
  id: number
  name: string
  budget_total: number
  budget_used: number
  starts_at: string
  ends_at: string
  status: 'active' | 'closed'
  created_at: string
}

export type Sale = {
  id: number
  external_id: string
  campaign_id: number
  seller_id: number
  product_id: number
  quantity: number
  unit_value: string
  status: 'approved' | 'canceled'
  created_at: string
}

export type SaleResponse = Sale & {
  points: number
}

export type SaleListItem = Sale & {
  campaign_name: string
  seller_name: string
  product_name: string
  points: number
}

export type WalletEntry = {
  id: number
  campaign_id: number
  sale_id: number
  type: 'credit' | 'debit'
  points: number
  description: string
  created_at: string
}

export type Wallet = {
  balance: number
  entries: WalletEntry[]
}

export type ProductInput = {
  name: string
  sku: string
  points_per_unit: number
}

export type User = {
  id: number
  name: string
  email: string
  role: Role
  created_at: string
}

export type SellerInput = {
  name: string
  email: string
  password: string
}

export type CampaignInput = {
  name: string
  budget_total: number
  starts_at: string
  ends_at: string
}

export type SaleInput = {
  external_id: string
  campaign_id: number
  seller_id: number
  product_id: number
  quantity: number
  unit_value: string
}

export type SaleDraft = Omit<SaleInput, 'external_id'>
