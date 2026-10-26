import { type ReactNode, useEffect } from 'react'
import { X } from 'lucide-react'
import { Button } from './button'
export function Dialog({ open, onClose, title, children }: { open: boolean; onClose: () => void; title: string; children: ReactNode }) {
  useEffect(() => { if (!open) return; const listener = (event: KeyboardEvent) => { if (event.key === 'Escape') onClose() }; window.addEventListener('keydown', listener); return () => window.removeEventListener('keydown', listener) }, [open, onClose])
  if (!open) return null
  return <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" onMouseDown={(e) => { if (e.target === e.currentTarget) onClose() }}>
    <section role="dialog" aria-modal="true" aria-label={title} className="max-h-[90vh] w-full max-w-xl overflow-auto rounded-2xl bg-white shadow-2xl">
      <header className="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-6 py-4"><h2 className="font-semibold text-slate-900">{title}</h2><Button type="button" variant="ghost" size="icon" onClick={onClose} aria-label="Close"><X size={18}/></Button></header>
      <div className="p-6">{children}</div>
    </section>
  </div>
}
