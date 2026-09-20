import type {
  Campaign,
  CampaignInput,
  Product,
  ProductInput,
  Sale,
  SaleInput,
  SaleListItem,
  SellerInput,
  User,
  Wallet,
} from '../types'

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

async function request<T>(path: string, token?: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers)
  headers.set('Accept', 'application/json')

  if (init.body) headers.set('Content-Type', 'application/json')
  if (token) headers.set('Authorization', `Bearer ${token}`)

  let response: Response
  try {
    response = await fetch(path, { ...init, headers })
  } catch {
    throw new ApiError(0, 'Não foi possível conectar ao servidor.')
  }

  const payload = (await response.json().catch(() => ({}))) as { error?: unknown }
  if (!response.ok) {
    throw new ApiError(
      response.status,
      typeof payload.error === 'string' ? payload.error : 'Não foi possível concluir a operação.',
    )
  }

  return payload as T
}

export const api = {
  login: (email: string, password: string) =>
    request<{ token: string }>('/auth/login', undefined, {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    }),
  listProducts: (token: string) => request<{ products: Product[] }>('/products', token),
  createProduct: (token: string, input: ProductInput) =>
    request<{ product: Product }>('/products', token, {
      method: 'POST',
      body: JSON.stringify(input),
    }),
  updateProduct: (token: string, id: number, input: ProductInput) =>
    request<{ product: Product }>(`/products/${id}`, token, {
      method: 'PUT',
      body: JSON.stringify(input),
    }),
  deactivateProduct: (token: string, id: number) =>
    request<{ product: Product }>(`/products/${id}`, token, { method: 'DELETE' }),
  listCampaigns: (token: string) => request<{ campaigns: Campaign[] }>('/campaigns', token),
  createCampaign: (token: string, input: CampaignInput) =>
    request<{ campaign: Campaign }>('/campaigns', token, {
      method: 'POST',
      body: JSON.stringify(input),
    }),
  listSellers: (token: string) => request<{ sellers: User[] }>('/users/sellers', token),
  createSeller: (token: string, input: SellerInput) =>
    request<{ user: User }>('/users', token, {
      method: 'POST',
      body: JSON.stringify(input),
    }),
  createSale: (token: string, input: SaleInput) =>
    request<{ sale: Sale }>('/sales', token, {
      method: 'POST',
      body: JSON.stringify(input),
    }),
  listSales: (token: string) => request<{ sales: SaleListItem[] }>('/sales', token),
  cancelSale: (token: string, externalId: string) =>
    request<{ sale: Sale; reversed_points: number }>(
      `/sales/${encodeURIComponent(externalId)}/cancel`,
      token,
      { method: 'POST' },
    ),
  wallet: (token: string) => request<{ wallet: Wallet }>('/me/wallet', token),
}
