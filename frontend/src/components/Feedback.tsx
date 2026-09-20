type FeedbackProps = {
  children: string
  tone?: 'error' | 'success' | 'info'
}

export function Feedback({ children, tone = 'error' }: FeedbackProps) {
  return (
    <p className={`feedback feedback-${tone}`} role={tone === 'error' ? 'alert' : 'status'}>
      {children}
    </p>
  )
}

export function Loading({ label = 'Carregando...' }: { label?: string }) {
  return <p className="loading" role="status">{label}</p>
}

export function EmptyState({ children }: { children: string }) {
  return <p className="empty-state">{children}</p>
}
