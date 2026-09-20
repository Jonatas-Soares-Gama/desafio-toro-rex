import { useEffect, useState, type FormEvent } from 'react'
import { EmptyState, Feedback, Loading } from '../../components/Feedback'
import { ApiError, api } from '../../lib/api'
import { getErrorMessage } from '../../lib/errors'
import { skuFromName } from '../../lib/sku'
import type { Product, ProductInput } from '../../types'

const emptyForm: ProductInput = { name: '', sku: '', points_per_unit: 1 }

type ProductsPageProps = {
  token: string
  onUnauthorized: () => void
}

export function ProductsPage({ token, onUnauthorized }: ProductsPageProps) {
  const [products, setProducts] = useState<Product[]>([])
  const [form, setForm] = useState<ProductInput>(emptyForm)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [formError, setFormError] = useState('')

  useEffect(() => {
    async function load() {
      setLoading(true)
      setError('')
      try {
        const response = await api.listProducts(token)
        setProducts(response.products)
      } catch (caught) {
        if (caught instanceof ApiError && caught.status === 401) onUnauthorized()
        setError(getErrorMessage(caught, 'Não foi possível carregar os produtos.'))
      } finally {
        setLoading(false)
      }
    }

    void load()
  }, [onUnauthorized, token])

  function resetForm() {
    setForm(emptyForm)
    setEditingId(null)
    setFormError('')
  }

  function handleNameChange(name: string) {
    setForm((current) => ({
      ...current,
      name,
      sku: editingId ? current.sku : skuFromName(name, products.map((product) => product.sku)),
    }))
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)
    setFormError('')

    try {
      const response = editingId
        ? await api.updateProduct(token, editingId, form)
        : await api.createProduct(token, form)

      setProducts((current) => editingId
        ? current.map((product) => product.id === response.product.id ? response.product : product)
        : [response.product, ...current])
      resetForm()
    } catch (caught) {
      if (caught instanceof ApiError && caught.status === 401) onUnauthorized()
      setFormError(caught instanceof ApiError && caught.status === 409
        ? 'Este nome gerou um SKU que já está em uso. Ajuste o nome do produto.'
        : getErrorMessage(caught, 'Não foi possível salvar o produto.'))
    } finally {
      setSaving(false)
    }
  }

  async function handleDeactivate(product: Product) {
    if (!window.confirm(`Inativar o produto ${product.name}?`)) return

    setError('')
    try {
      const response = await api.deactivateProduct(token, product.id)
      setProducts((current) => current.map((item) => item.id === response.product.id ? response.product : item))
    } catch (caught) {
      if (caught instanceof ApiError && caught.status === 401) onUnauthorized()
      setError(getErrorMessage(caught, 'Não foi possível inativar o produto.'))
    }
  }

  return (
    <>
      <header className="page-header">
        <div>
          <p className="eyebrow">Administração</p>
          <h1>Produtos</h1>
          <p className="page-description">Gerencie o catálogo que alimenta a pontuação das vendas.</p>
        </div>
        <span className="header-count">{products.length} {products.length === 1 ? 'produto' : 'produtos'}</span>
      </header>

      <div className="content-grid">
        <section className="panel" aria-labelledby="product-form-title">
          <div className="panel-heading">
            <div>
              <p className="eyebrow">Catálogo</p>
              <h2 id="product-form-title">{editingId ? 'Editar produto' : 'Novo produto'}</h2>
            </div>
            {editingId && <button className="button button-subtle" onClick={resetForm} type="button">Cancelar</button>}
          </div>
          <form className="form-stack product-form" onSubmit={handleSubmit}>
            <div className="field">
              <label htmlFor="product-name">Nome</label>
              <input id="product-name" onChange={(event) => handleNameChange(event.target.value)} required value={form.name} />
            </div>
            <div className="field-row">
              <div className="field">
                <label htmlFor="product-sku">SKU automático</label>
                <input aria-describedby="product-sku-help" id="product-sku" readOnly value={form.sku} />
                <span className="field-help" id="product-sku-help">Gerado a partir do nome. O SKU não muda ao editar o produto.</span>
              </div>
              <div className="field">
                <label htmlFor="product-points">Pontos por unidade</label>
                <input id="product-points" min="1" onChange={(event) => setForm({ ...form, points_per_unit: Number(event.target.value) })} required type="number" value={form.points_per_unit} />
              </div>
            </div>
            {formError && <Feedback>{formError}</Feedback>}
            <button className="button button-primary" disabled={saving} type="submit">
              {saving ? 'Salvando...' : editingId ? 'Salvar alterações' : 'Cadastrar produto'}
            </button>
          </form>
        </section>

        <section className="panel panel-wide" aria-labelledby="product-list-title">
          <div className="panel-heading">
            <div>
              <p className="eyebrow">Visão geral</p>
              <h2 id="product-list-title">Catálogo cadastrado</h2>
            </div>
          </div>
          {error && <Feedback>{error}</Feedback>}
          {loading ? <Loading label="Carregando produtos..." /> : products.length === 0 ? <EmptyState>Nenhum produto cadastrado ainda.</EmptyState> : (
            <div className="table-wrap">
              <table>
                <caption className="sr-only">Produtos cadastrados</caption>
                <thead><tr><th>Produto</th><th>SKU</th><th>Pontos</th><th>Status</th><th><span className="sr-only">Ações</span></th></tr></thead>
                <tbody>
                  {products.map((product) => (
                    <tr key={product.id}>
                      <td data-label="Produto"><strong>{product.name}</strong></td>
                      <td data-label="SKU">{product.sku}</td>
                      <td data-label="Pontos">{product.points_per_unit}</td>
                      <td data-label="Status"><span className={product.active ? 'status status-success' : 'status status-muted'}>{product.active ? 'Ativo' : 'Inativo'}</span></td>
                      <td className="row-actions" data-label="Ações">
                        <button className="text-button" onClick={() => { setEditingId(product.id); setForm({ name: product.name, sku: product.sku, points_per_unit: product.points_per_unit }); setFormError('') }} type="button">Editar</button>
                        {product.active && <button className="text-button text-danger" onClick={() => void handleDeactivate(product)} type="button">Inativar</button>}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>
      </div>
    </>
  )
}
