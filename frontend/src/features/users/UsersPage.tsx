import { useEffect, useState, type FormEvent } from 'react'
import { EmptyState, Feedback, Loading } from '../../components/Feedback'
import { ApiError, api } from '../../lib/api'
import { getErrorMessage } from '../../lib/errors'
import type { SellerInput, User } from '../../types'

const emptyForm: SellerInput = { name: '', email: '', password: '' }

type UsersPageProps = {
  token: string
  onUnauthorized: () => void
}

export function UsersPage({ token, onUnauthorized }: UsersPageProps) {
  const [sellers, setSellers] = useState<User[]>([])
  const [form, setForm] = useState<SellerInput>(emptyForm)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [formError, setFormError] = useState('')

  async function loadSellers() {
    setLoading(true)
    setError('')
    try {
      const response = await api.listSellers(token)
      setSellers(response.sellers)
    } catch (caught) {
      if (caught instanceof ApiError && caught.status === 401) onUnauthorized()
      setError(getErrorMessage(caught, 'Não foi possível carregar os sellers.'))
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void loadSellers()
  }, [token])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)
    setFormError('')
    try {
      const response = await api.createSeller(token, form)
      setSellers((current) => [...current, response.user].sort((left, right) => left.name.localeCompare(right.name)))
      setForm(emptyForm)
    } catch (caught) {
      if (caught instanceof ApiError && caught.status === 401) onUnauthorized()
      setFormError(getErrorMessage(caught, 'Não foi possível cadastrar o seller.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <>
      <header className="page-header">
        <div>
          <p className="eyebrow">Administração</p>
          <h1>Usuários</h1>
          <p className="page-description">Cadastre sellers e use nomes claros na hora de lançar uma venda.</p>
        </div>
        <span className="header-count">{sellers.length} {sellers.length === 1 ? 'seller' : 'sellers'}</span>
      </header>

      <div className="content-grid">
        <section className="panel" aria-labelledby="seller-form-title">
          <div className="panel-heading"><div><p className="eyebrow">Equipe</p><h2 id="seller-form-title">Cadastrar seller</h2></div></div>
          <form className="form-stack" onSubmit={handleSubmit}>
            <div className="field">
              <label htmlFor="seller-name">Nome</label>
              <input autoComplete="name" id="seller-name" onChange={(event) => setForm({ ...form, name: event.target.value })} required value={form.name} />
            </div>
            <div className="field">
              <label htmlFor="seller-email">E-mail</label>
              <input autoComplete="email" id="seller-email" onChange={(event) => setForm({ ...form, email: event.target.value })} required type="email" value={form.email} />
            </div>
            <div className="field">
              <label htmlFor="seller-password">Senha inicial</label>
              <input autoComplete="new-password" id="seller-password" minLength={8} onChange={(event) => setForm({ ...form, password: event.target.value })} required type="password" value={form.password} />
              <span className="field-help">Use pelo menos 8 caracteres.</span>
            </div>
            {formError && <Feedback>{formError}</Feedback>}
            <button className="button button-primary" disabled={saving} type="submit">{saving ? 'Cadastrando...' : 'Cadastrar seller'}</button>
          </form>
        </section>

        <section className="panel panel-wide" aria-labelledby="seller-list-title">
          <div className="panel-heading"><div><p className="eyebrow">Equipe</p><h2 id="seller-list-title">Sellers cadastrados</h2></div></div>
          {error && <Feedback>{error}</Feedback>}
          {loading ? <Loading label="Carregando sellers..." /> : sellers.length === 0 ? <EmptyState>Nenhum seller cadastrado ainda.</EmptyState> : (
            <div className="table-wrap">
              <table>
                <caption className="sr-only">Sellers cadastrados</caption>
                <thead><tr><th>Nome</th><th>E-mail</th><th>Papel</th><th>Cadastro</th></tr></thead>
                <tbody>
                  {sellers.map((seller) => (
                    <tr key={seller.id}>
                      <td data-label="Nome"><strong>{seller.name}</strong></td>
                      <td data-label="E-mail">{seller.email}</td>
                      <td data-label="Papel"><span className="status status-success">Seller</span></td>
                      <td data-label="Cadastro">{new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short' }).format(new Date(seller.created_at.replace(' ', 'T')))}</td>
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
