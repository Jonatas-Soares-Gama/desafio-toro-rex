import { useEffect, useState, type FormEvent } from 'react'
import { EmptyState, Feedback, Loading } from '../../components/Feedback'
import { ApiError, api } from '../../lib/api'
import { downloadCsv, salesToCsv } from '../../lib/csv'
import { getErrorMessage } from '../../lib/errors'
import type { Campaign, Product, SaleDraft, SaleListItem, User } from '../../types'

const emptyForm: SaleDraft = {
  campaign_id: 0,
  seller_id: 0,
  product_id: 0,
  quantity: 1,
  unit_value: '0.00',
}

const dateFormatter = new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' })

function formatDate(value: string) {
  return dateFormatter.format(new Date(value.replace(' ', 'T')))
}

type SalesPageProps = {
  token: string
  onUnauthorized: () => void
}

export function SalesPage({ token, onUnauthorized }: SalesPageProps) {
  const [products, setProducts] = useState<Product[]>([])
  const [campaigns, setCampaigns] = useState<Campaign[]>([])
  const [sellers, setSellers] = useState<User[]>([])
  const [sales, setSales] = useState<SaleListItem[]>([])
  const [form, setForm] = useState<SaleDraft>(emptyForm)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [cancellingId, setCancellingId] = useState<number | null>(null)
  const [error, setError] = useState('')
  const [feedback, setFeedback] = useState('')

  useEffect(() => {
    async function loadOptions() {
      setLoading(true)
      setError('')
      try {
        const [productResponse, campaignResponse, sellerResponse, saleResponse] = await Promise.all([
          api.listProducts(token),
          api.listCampaigns(token),
          api.listSellers(token),
          api.listSales(token),
        ])
        setProducts(productResponse.products)
        setCampaigns(campaignResponse.campaigns)
        setSellers(sellerResponse.sellers)
        setSales(saleResponse.sales)
        setForm((current) => ({
          ...current,
          product_id: productResponse.products.find((product) => product.active)?.id ?? 0,
          campaign_id: campaignResponse.campaigns.find((campaign) => campaign.status === 'active')?.id ?? 0,
          seller_id: sellerResponse.sellers[0]?.id ?? 0,
        }))
      } catch (caught) {
        if (caught instanceof ApiError && caught.status === 401) onUnauthorized()
        setError(getErrorMessage(caught, 'Não foi possível carregar os dados da operação.'))
      } finally {
        setLoading(false)
      }
    }

    void loadOptions()
  }, [onUnauthorized, token])

  async function handleSale(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)
    setFeedback('')
    setError('')
    try {
      const response = await api.createSale(token, {
        ...form,
        external_id: `manual-${crypto.randomUUID()}`,
      })
      const product = products.find((item) => item.id === form.product_id)
      const campaign = campaigns.find((item) => item.id === form.campaign_id)
      const seller = sellers.find((item) => item.id === form.seller_id)
      setSales((current) => [{
        ...response.sale,
        campaign_name: campaign?.name ?? 'Campanha',
        seller_name: seller?.name ?? 'Seller',
        product_name: product?.name ?? 'Produto',
        points: response.sale.points,
      }, ...current])
      setFeedback(`Venda ${response.sale.external_id} registrada com sucesso.`)
      setForm({ ...emptyForm, product_id: form.product_id, campaign_id: form.campaign_id, seller_id: form.seller_id })
    } catch (caught) {
      if (caught instanceof ApiError && caught.status === 401) onUnauthorized()
      setError(getErrorMessage(caught, 'Não foi possível registrar a venda.'))
    } finally {
      setSaving(false)
    }
  }

  async function handleCancellation(sale: SaleListItem) {
    if (!window.confirm(`Cancelar a venda de ${sale.product_name} para ${sale.seller_name}?`)) return

    setCancellingId(sale.id)
    setFeedback('')
    setError('')
    try {
      const response = await api.cancelSale(token, sale.external_id)
      setSales((current) => current.map((item) => item.id === sale.id ? { ...item, status: 'canceled' } : item))
      setFeedback(`Venda cancelada. ${response.reversed_points.toLocaleString('pt-BR')} pontos estornados.`)
    } catch (caught) {
      if (caught instanceof ApiError && caught.status === 401) onUnauthorized()
      setError(getErrorMessage(caught, 'Não foi possível cancelar a venda.'))
    } finally {
      setCancellingId(null)
    }
  }

  function handleExport() {
    const date = new Date().toISOString().slice(0, 10)
    downloadCsv(`vendas-${date}.csv`, salesToCsv(sales))
  }

  const activeProducts = products.filter((product) => product.active)
  const activeCampaigns = campaigns.filter((campaign) => campaign.status === 'active')

  return (
    <>
      <header className="page-header">
        <div>
          <p className="eyebrow">Operação</p>
          <h1>Vendas</h1>
          <p className="page-description">Registre vendas aprovadas e reverta operações dentro da janela permitida.</p>
        </div>
      </header>

      {error && <Feedback>{error}</Feedback>}
      {feedback && <Feedback tone="success">{feedback}</Feedback>}
      {loading ? <Loading label="Carregando opções e histórico..." /> : (
        <div className="content-grid">
          <section className="panel" aria-labelledby="sale-form-title">
            <div className="panel-heading"><div><p className="eyebrow">Pontuação</p><h2 id="sale-form-title">Lançar venda</h2></div></div>
            {activeProducts.length === 0 || activeCampaigns.length === 0 || sellers.length === 0 ? (
              <EmptyState>Cadastre um produto, uma campanha ativa e pelo menos um seller antes de lançar uma venda.</EmptyState>
            ) : (
              <form className="form-stack" onSubmit={handleSale}>
                <div className="field">
                  <span className="field-label">Identificador da venda</span>
                  <span className="field-help">Gerado automaticamente para manter a idempotência da API.</span>
                </div>
                <div className="field-row">
                  <div className="field">
                    <label htmlFor="sale-seller-id">Seller</label>
                    <select id="sale-seller-id" onChange={(event) => setForm({ ...form, seller_id: Number(event.target.value) })} required value={form.seller_id}>
                      <option disabled value={0}>Selecione</option>
                      {sellers.map((seller) => <option key={seller.id} value={seller.id}>{seller.name} · {seller.email}</option>)}
                    </select>
                  </div>
                  <div className="field">
                    <label htmlFor="sale-quantity">Quantidade</label>
                    <input id="sale-quantity" min="1" onChange={(event) => setForm({ ...form, quantity: Number(event.target.value) })} required type="number" value={form.quantity} />
                  </div>
                </div>
                <div className="field-row">
                  <div className="field">
                    <label htmlFor="sale-product">Produto</label>
                    <select id="sale-product" onChange={(event) => setForm({ ...form, product_id: Number(event.target.value) })} required value={form.product_id}>
                      <option disabled value={0}>Selecione</option>
                      {activeProducts.map((product) => <option key={product.id} value={product.id}>{product.name} · {product.points_per_unit} pts/un.</option>)}
                    </select>
                  </div>
                  <div className="field">
                    <label htmlFor="sale-campaign">Campanha</label>
                    <select id="sale-campaign" onChange={(event) => setForm({ ...form, campaign_id: Number(event.target.value) })} required value={form.campaign_id}>
                      <option disabled value={0}>Selecione</option>
                      {activeCampaigns.map((campaign) => <option key={campaign.id} value={campaign.id}>{campaign.name}</option>)}
                    </select>
                  </div>
                </div>
                <div className="field">
                  <label htmlFor="sale-unit-value">Valor unitário</label>
                  <input id="sale-unit-value" min="0" onChange={(event) => setForm({ ...form, unit_value: event.target.value })} required step="0.01" type="number" value={form.unit_value} />
                </div>
                <button className="button button-primary" disabled={saving} type="submit">{saving ? 'Registrando...' : 'Registrar venda'}</button>
              </form>
            )}
          </section>

          <section className="panel panel-wide sales-history" aria-labelledby="sales-history-title">
            <div className="panel-heading">
              <div>
                <p className="eyebrow">Operação</p>
                <h2 id="sales-history-title">Histórico de vendas</h2>
              </div>
              <div className="panel-heading-actions">
                <span className="header-count">{sales.length} {sales.length === 1 ? 'venda' : 'vendas'}</span>
                <button className="button button-subtle" disabled={sales.length === 0} onClick={handleExport} type="button">Exportar CSV</button>
              </div>
            </div>
            {sales.length === 0 ? <EmptyState>Nenhuma venda registrada ainda.</EmptyState> : (
              <div className="table-wrap">
                <table>
                  <caption className="sr-only">Histórico de vendas</caption>
                  <thead><tr><th>Venda</th><th>Seller</th><th>Produto</th><th>Campanha</th><th>Qtd.</th><th>Pontos</th><th>Status</th><th>Data</th><th><span className="sr-only">Ações</span></th></tr></thead>
                  <tbody>
                    {sales.map((sale) => (
                      <tr key={sale.id}>
                        <td data-label="Venda"><strong>#{sale.id}</strong><span className="entry-description">{sale.external_id}</span></td>
                        <td data-label="Seller">{sale.seller_name}</td>
                        <td data-label="Produto">{sale.product_name}</td>
                        <td data-label="Campanha">{sale.campaign_name}</td>
                        <td data-label="Qtd.">{sale.quantity}</td>
                        <td className="numeric" data-label="Pontos">{sale.points.toLocaleString('pt-BR')}</td>
                        <td data-label="Status"><span className={sale.status === 'approved' ? 'status status-success' : 'status status-muted'}>{sale.status === 'approved' ? 'Aprovada' : 'Cancelada'}</span></td>
                        <td data-label="Data">{formatDate(sale.created_at)}</td>
                        <td className="row-actions" data-label="Ações">
                          {sale.status === 'approved' && <button className="text-button text-danger" disabled={cancellingId === sale.id} onClick={() => void handleCancellation(sale)} type="button">{cancellingId === sale.id ? 'Cancelando...' : 'Cancelar'}</button>}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </section>
        </div>
      )}
    </>
  )
}
