import { useEffect, useState, type FormEvent } from 'react'
import { EmptyState, Feedback, Loading } from '../../components/Feedback'
import { ApiError, api } from '../../lib/api'
import { getErrorMessage } from '../../lib/errors'
import type { Campaign, CampaignInput } from '../../types'

const emptyForm: CampaignInput = {
  name: '',
  budget_total: 1000,
  starts_at: '',
  ends_at: '',
}

type CampaignsPageProps = {
  token: string
  onUnauthorized: () => void
}

function toApiDateTime(value: string, endOfDay = false): string {
  return value ? `${value} ${endOfDay ? '23:59:59' : '00:00:00'}` : value
}

function formatDate(value: string): string {
  return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short' }).format(new Date(value.replace(' ', 'T')))
}

export function CampaignsPage({ token, onUnauthorized }: CampaignsPageProps) {
  const [campaigns, setCampaigns] = useState<Campaign[]>([])
  const [form, setForm] = useState<CampaignInput>(emptyForm)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [formError, setFormError] = useState('')

  useEffect(() => {
    async function load() {
      setLoading(true)
      setError('')
      try {
        const response = await api.listCampaigns(token)
        setCampaigns(response.campaigns)
      } catch (caught) {
        if (caught instanceof ApiError && caught.status === 401) onUnauthorized()
        setError(getErrorMessage(caught, 'Não foi possível carregar as campanhas.'))
      } finally {
        setLoading(false)
      }
    }

    void load()
  }, [onUnauthorized, token])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)
    setFormError('')

    try {
      const response = await api.createCampaign(token, {
        ...form,
        starts_at: toApiDateTime(form.starts_at),
        ends_at: toApiDateTime(form.ends_at, true),
      })
      setCampaigns((current) => [response.campaign, ...current])
      setForm(emptyForm)
    } catch (caught) {
      if (caught instanceof ApiError && caught.status === 401) onUnauthorized()
      setFormError(getErrorMessage(caught, 'Não foi possível criar a campanha.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <>
      <header className="page-header">
        <div>
          <p className="eyebrow">Administração</p>
          <h1>Campanhas</h1>
          <p className="page-description">Acompanhe a verba disponível e crie novos períodos de incentivo.</p>
        </div>
        <span className="header-count">{campaigns.length} {campaigns.length === 1 ? 'campanha' : 'campanhas'}</span>
      </header>

      <div className="content-grid">
        <section className="panel" aria-labelledby="campaign-form-title">
          <div className="panel-heading">
            <div>
              <p className="eyebrow">Novo ciclo</p>
              <h2 id="campaign-form-title">Criar campanha</h2>
            </div>
          </div>
          <form className="form-stack" onSubmit={handleSubmit}>
            <div className="field">
              <label htmlFor="campaign-name">Nome</label>
              <input id="campaign-name" onChange={(event) => setForm({ ...form, name: event.target.value })} required value={form.name} />
            </div>
            <div className="field">
              <label htmlFor="campaign-budget">Verba total em pontos</label>
              <input id="campaign-budget" min="1" onChange={(event) => setForm({ ...form, budget_total: Number(event.target.value) })} required type="number" value={form.budget_total} />
            </div>
            <div className="field-row">
              <div className="field">
                <label htmlFor="campaign-start">Início</label>
                <input aria-describedby="campaign-date-help" id="campaign-start" lang="pt-BR" onChange={(event) => setForm({ ...form, starts_at: event.target.value })} required type="date" value={form.starts_at} />
              </div>
              <div className="field">
                <label htmlFor="campaign-end">Fim</label>
                <input aria-describedby="campaign-date-help" id="campaign-end" lang="pt-BR" min={form.starts_at || undefined} onChange={(event) => setForm({ ...form, ends_at: event.target.value })} required type="date" value={form.ends_at} />
              </div>
            </div>
            <span className="field-help" id="campaign-date-help">Escolha as datas no formato dia/mês/ano. O início considera 00:00 e o fim considera 23:59.</span>
            {formError && <Feedback>{formError}</Feedback>}
            <button className="button button-primary" disabled={saving} type="submit">{saving ? 'Criando...' : 'Criar campanha'}</button>
          </form>
        </section>

        <section className="panel panel-wide" aria-labelledby="campaign-list-title">
          <div className="panel-heading">
            <div>
              <p className="eyebrow">Orçamento</p>
              <h2 id="campaign-list-title">Campanhas cadastradas</h2>
            </div>
          </div>
          {error && <Feedback>{error}</Feedback>}
          {loading ? <Loading label="Carregando campanhas..." /> : campaigns.length === 0 ? <EmptyState>Nenhuma campanha cadastrada ainda.</EmptyState> : (
            <div className="campaign-list">
              {campaigns.map((campaign) => {
                const usage = campaign.budget_total > 0 ? Math.min(100, (campaign.budget_used / campaign.budget_total) * 100) : 0
                return (
                  <article className="campaign-row" key={campaign.id}>
                    <div className="campaign-row-main">
                      <div>
                        <h3>{campaign.name}</h3>
                        <p>{formatDate(campaign.starts_at)} até {formatDate(campaign.ends_at)}</p>
                      </div>
                      <span className={campaign.status === 'active' ? 'status status-success' : 'status status-muted'}>{campaign.status === 'active' ? 'Ativa' : 'Fechada'}</span>
                    </div>
                    <div className="budget-line"><span>Verba utilizada</span><strong>{campaign.budget_used.toLocaleString('pt-BR')} / {campaign.budget_total.toLocaleString('pt-BR')} pts</strong></div>
                    <progress aria-label={`Uso da verba da campanha ${campaign.name}`} max="100" value={usage} />
                  </article>
                )
              })}
            </div>
          )}
        </section>
      </div>
    </>
  )
}
