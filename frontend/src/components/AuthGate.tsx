import { Navigate, Outlet } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/api/client'
import type { User } from '@/types/crm'
export function useCurrentUser() {
  return useQuery({ queryKey: ['auth','user'], queryFn: async () => (await api.get<User>('/user')).data, retry: false, staleTime: 60_000 })
}
export function AuthGate() {
  const user = useCurrentUser()
  if (user.isPending) return <div className="grid min-h-screen place-items-center text-sm text-slate-500">Loading your workspace…</div>
  if (!user.data) return <Navigate to="/login" replace />
  return <Outlet />
}
export function PermissionGate({ permission, children }: { permission: string; children: React.ReactNode }) {
  const user = useCurrentUser()
  const allowed = user.data?.roles.some((role) => role.permissions?.some((item) => item.slug === permission))
  return allowed ? children : <div className="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">Your account does not have permission to open this page.</div>
}
export function RoleGate({ role, children }: { role: string; children: React.ReactNode }) {
  const user = useCurrentUser()
  if (user.isPending) return <div className="grid min-h-screen place-items-center text-sm text-slate-500">Loading your workspace…</div>
  return user.data?.roles.some((item) => item.slug === role) ? children : <div className="rounded-xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-800">You do not have access to this page.</div>
}
