import type { ReactNode } from 'react'
import type { Role } from '../types'

export type AdminView = 'products' | 'campaigns' | 'sales' | 'users'

type ShellProps = {
  role: Role
  activeView?: AdminView
  onNavigate?: (view: AdminView) => void
  onLogout: () => void
  children: ReactNode
}

const adminNavigation: { id: AdminView; label: string }[] = [
  { id: 'products', label: 'Produtos' },
  { id: 'campaigns', label: 'Campanhas' },
  { id: 'sales', label: 'Vendas' },
  { id: 'users', label: 'Usuários' },
]

export function Shell({ role, activeView, onNavigate, onLogout, children }: ShellProps) {
  return (
    <div className="app-shell">
      <aside className="sidebar">
        <div>
          <a className="brand" href="#top" aria-label="Vendeu, Ganhou - início">
            <span className="brand-mark" aria-hidden="true">VG</span>
            <span>Vendeu, Ganhou</span>
          </a>
          <p className="role-label">Área {role === 'admin' ? 'administrativa' : 'do vendedor'}</p>
        </div>

        <nav className="main-nav" aria-label="Navegação principal">
          {role === 'admin' ? (
            adminNavigation.map((item) => (
              <button
                className={activeView === item.id ? 'nav-link is-active' : 'nav-link'}
                key={item.id}
                onClick={() => onNavigate?.(item.id)}
                type="button"
              >
                {item.label}
              </button>
            ))
          ) : (
            <span className="nav-link is-active">Minha carteira</span>
          )}
        </nav>

        <button className="logout-button" onClick={onLogout} type="button">Sair</button>
      </aside>

      <main className="main-content" id="top">{children}</main>
    </div>
  )
}
