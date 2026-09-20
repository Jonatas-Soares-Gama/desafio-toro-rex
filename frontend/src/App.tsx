import { useCallback, useState } from 'react'
import { Shell, type AdminView } from './components/Shell'
import { clearToken, getRole, getToken, saveToken } from './lib/auth'
import { LoginPage } from './features/auth/LoginPage'
import { CampaignsPage } from './features/campaigns/CampaignsPage'
import { ProductsPage } from './features/products/ProductsPage'
import { SalesPage } from './features/sales/SalesPage'
import { WalletPage } from './features/wallet/WalletPage'
import { UsersPage } from './features/users/UsersPage'

function App() {
  const [token, setToken] = useState(() => getToken())
  const [activeView, setActiveView] = useState<AdminView>('products')
  const role = token ? getRole(token) : null

  const handleLogin = (nextToken: string) => {
    saveToken(nextToken)
    setToken(nextToken)
  }

  const handleLogout = useCallback(() => {
    clearToken()
    setToken(null)
  }, [])

  const handleUnauthorized = useCallback(() => {
    clearToken()
    setToken(null)
  }, [])

  if (!token || !role) {
    return <LoginPage onLogin={handleLogin} />
  }

  if (role === 'seller') {
    return (
      <Shell role="seller" onLogout={handleLogout}>
        <WalletPage token={token} onUnauthorized={handleUnauthorized} />
      </Shell>
    )
  }

  return (
    <Shell
      activeView={activeView}
      onLogout={handleLogout}
      onNavigate={setActiveView}
      role="admin"
    >
      {activeView === 'products' && <ProductsPage token={token} onUnauthorized={handleUnauthorized} />}
      {activeView === 'campaigns' && <CampaignsPage token={token} onUnauthorized={handleUnauthorized} />}
      {activeView === 'sales' && <SalesPage token={token} onUnauthorized={handleUnauthorized} />}
      {activeView === 'users' && <UsersPage token={token} onUnauthorized={handleUnauthorized} />}
    </Shell>
  )
}

export default App
