import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [react()],
    server: {
      host: '0.0.0.0',
      port: 5173,
      proxy: {
        '/auth': { target: env.BACKEND_URL || 'http://localhost:8080' },
        '/products': { target: env.BACKEND_URL || 'http://localhost:8080' },
        '/campaigns': { target: env.BACKEND_URL || 'http://localhost:8080' },
        '/sales': { target: env.BACKEND_URL || 'http://localhost:8080' },
        '/users': { target: env.BACKEND_URL || 'http://localhost:8080' },
        '/me': { target: env.BACKEND_URL || 'http://localhost:8080' },
      },
    },
  }
})
