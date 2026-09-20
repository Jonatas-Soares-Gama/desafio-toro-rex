import { useState, type FormEvent } from 'react'
import { Feedback } from '../../components/Feedback'
import { ApiError, api } from '../../lib/api'

type LoginPageProps = {
  onLogin: (token: string) => void
}

export function LoginPage({ onLogin }: LoginPageProps) {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setSubmitting(true)

    try {
      const response = await api.login(email.trim(), password)
      onLogin(response.token)
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'Não foi possível entrar agora.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="login-page">
      <section className="login-panel" aria-labelledby="login-title">
        <div className="login-intro">
          <span className="brand-mark" aria-hidden="true">VG</span>
          <p className="eyebrow">Plataforma de incentivo</p>
          <h1 id="login-title">Vendeu, ganhou.</h1>
          <p>Entre para acompanhar campanhas, vendas e pontos em um só lugar.</p>
        </div>

        <form className="form-stack" onSubmit={handleSubmit}>
          <div className="field">
            <label htmlFor="email">E-mail</label>
            <input
              autoComplete="email"
              id="email"
              onChange={(event) => setEmail(event.target.value)}
              required
              type="email"
              value={email}
            />
          </div>
          <div className="field">
            <label htmlFor="password">Senha</label>
            <input
              autoComplete="current-password"
              id="password"
              onChange={(event) => setPassword(event.target.value)}
              required
              type="password"
              value={password}
            />
          </div>

          {error && <Feedback>{error}</Feedback>}
          <button className="button button-primary button-wide" disabled={submitting} type="submit">
            {submitting ? 'Entrando...' : 'Entrar'}
          </button>
        </form>
      </section>
    </main>
  )
}
