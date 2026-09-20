import { ApiError } from './api'

export function getErrorMessage(error: unknown, fallback = 'Não foi possível concluir a operação.'): string {
  if (!(error instanceof ApiError)) return fallback
  if (error.status === 0) return 'Não foi possível conectar ao servidor.'
  if (error.status === 401) return 'Sua sessão expirou. Entre novamente.'
  if (error.status === 403) return 'Você não tem permissão para esta ação.'
  if (error.status === 404) return 'O recurso não foi encontrado.'
  if (error.status === 409) return error.message
  if (error.status === 422) return error.message
  return fallback
}
