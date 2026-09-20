import { useEffect, useState } from 'react'
import { EmptyState, Feedback, Loading } from '../../components/Feedback'
import { ApiError, api } from '../../lib/api'
import { getErrorMessage } from '../../lib/errors'
import type { Wallet } from '../../types'

type WalletPageProps = {
  token: string
  onUnauthorized: () => void
}

export function WalletPage({ token, onUnauthorized }: WalletPageProps) {
  const [wallet, setWallet] = useState<Wallet | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  async function loadWallet() {
    setLoading(true)
    setError('')
    try {
      const response = await api.wallet(token)
      setWallet(response.wallet)
    } catch (caught) {
      if (caught instanceof ApiError && caught.status === 401) onUnauthorized()
      setError(getErrorMessage(caught, 'Não foi possível carregar sua carteira.'))
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void loadWallet()
  }, [token])

  return (
    <>
      <header className="page-header">
        <div>
          <p className="eyebrow">Minha área</p>
          <h1>Minha carteira</h1>
          <p className="page-description">Seu saldo e todas as movimentações de pontos em um só lugar.</p>
        </div>
        <button className="button button-subtle" disabled={loading} onClick={() => void loadWallet()} type="button">Atualizar</button>
      </header>

      {loading && !wallet ? <Loading label="Carregando sua carteira..." /> : error ? (
        <section className="panel narrow-panel">
          <Feedback>{error}</Feedback>
          <button className="button button-secondary" onClick={() => void loadWallet()} type="button">Tentar novamente</button>
        </section>
      ) : wallet && (
        <>
          <section className="balance-panel" aria-labelledby="balance-title">
            <div>
              <p className="eyebrow">Saldo disponível</p>
              <h2 id="balance-title">{wallet.balance.toLocaleString('pt-BR')} <span>pontos</span></h2>
            </div>
            <p className="balance-note">Calculado a partir do seu extrato.</p>
          </section>

          <section className="panel" aria-labelledby="ledger-title">
            <div className="panel-heading">
              <div>
                <p className="eyebrow">Histórico</p>
                <h2 id="ledger-title">Extrato de pontos</h2>
              </div>
              <span className="header-count">{wallet.entries.length} {wallet.entries.length === 1 ? 'movimentação' : 'movimentações'}</span>
            </div>
            {wallet.entries.length === 0 ? <EmptyState>Seu extrato aparecerá aqui quando uma venda for registrada.</EmptyState> : (
              <div className="table-wrap">
                <table>
                  <caption className="sr-only">Extrato de pontos</caption>
                  <thead><tr><th>Movimentação</th><th>Venda</th><th>Data</th><th className="numeric">Pontos</th></tr></thead>
                  <tbody>
                    {wallet.entries.map((entry) => (
                      <tr key={entry.id}>
                        <td data-label="Movimentação"><span className={entry.type === 'credit' ? 'ledger-type ledger-credit' : 'ledger-type ledger-debit'}>{entry.type === 'credit' ? 'Crédito' : 'Débito'}</span><span className="entry-description">{entry.description}</span></td>
                        <td data-label="Venda">#{entry.sale_id}</td>
                        <td data-label="Data">{new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(entry.created_at.replace(' ', 'T')))}</td>
                        <td className="numeric" data-label="Pontos"><strong className={entry.type === 'credit' ? 'points-positive' : 'points-negative'}>{entry.type === 'credit' ? '+' : '-'}{entry.points.toLocaleString('pt-BR')}</strong></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </section>
        </>
      )}
    </>
  )
}
