import { useState } from 'react'
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Activity, BarChart3, Bell, BriefcaseBusiness, Building2, CalendarClock, ChevronDown, ChevronLeft, ChevronRight, CircleDollarSign, ClipboardCheck, FileText, LayoutDashboard, LogOut, Mail, Menu, Plane, UserRound, Users, X } from 'lucide-react'
import { authApi } from '@/api/auth'
import { useCurrentUser } from './AuthGate'
import { api } from '@/api/client'
import { cn } from '@/lib/utils'
import { useBranding } from '@/hooks/useBranding'

const navItems = [
  { label: 'Dashboard', to: '/', icon: LayoutDashboard, permission: 'dashboard.view' },
  { label: 'Reports', to: '/reports', icon: BarChart3, permission: 'reports.view' },
  { label: 'Clients', to: '/clients', icon: Users, permission: 'clients.view' },
  { label: 'Leads', to: '/leads', icon: Activity, permission: 'clients.view' },
  { label: 'Applications', to: '/applications', icon: BriefcaseBusiness, permission: 'applications.view' },
  { label: 'Documents', to: '/documents', icon: FileText, permission: 'documents.view' },
  { label: 'Follow-ups', to: '/follow-ups', icon: CalendarClock, permission: 'followups.view' },
  { label: 'Payments', to: '/payments', icon: CircleDollarSign, permission: 'payments.view' },
  { label: 'Services', to: '/services', icon: Plane, permission: 'services.manage' },
  { label: 'Team & access', to: '/users', icon: ClipboardCheck, permission: 'users.manage' },
  { label: 'Email settings', to: '/settings/email', icon: Mail, permission: 'users.manage', superAdminOnly: true },
  { label: 'Branding', to: '/settings/branding', icon: Building2, permission: 'users.manage', superAdminOnly: true },
]
export function AppShell() {
  const user = useCurrentUser().data
  const branding = useBranding()
  const [mobileOpen, setMobileOpen] = useState(false)
  const [collapsed, setCollapsed] = useState(false)
  const [accountOpen, setAccountOpen] = useState(false)
  const [notificationsOpen, setNotificationsOpen] = useState(false)
  const navigate = useNavigate()
  const qc = useQueryClient()
  const logout = useMutation({ mutationFn: authApi.logout, onSuccess: () => { qc.clear(); navigate('/login') } })
  const permissions = new Set(user?.roles.flatMap((role) => role.permissions?.map((permission) => permission.slug) ?? []) ?? [])
  const initials = user?.name.split(' ').map((part) => part[0]).slice(0,2).join('').toUpperCase()
  const notifications = useQuery({ queryKey: ['dashboard-notifications'], enabled: permissions.has('dashboard.view'), refetchInterval: 60_000, queryFn: async () => (await api.get<{stats:{pending_documents:number;follow_ups_due_today:number;overdue_follow_ups:number}}>('/dashboard')).data })
  const notificationCount = (notifications.data?.stats.follow_ups_due_today ?? 0) + (notifications.data?.stats.overdue_follow_ups ?? 0) + (permissions.has('documents.view') ? notifications.data?.stats.pending_documents ?? 0 : 0)
  const sidebar = <aside className={cn('flex h-full flex-col bg-[#111a2e] text-slate-300 transition-all', collapsed ? 'w-[76px]' : 'w-[250px]')}>
    <div className={cn('flex h-[74px] items-center gap-3 border-b border-white/10 px-5', collapsed && 'justify-center px-2')}><span className="grid size-9 shrink-0 place-items-center overflow-hidden rounded-xl bg-indigo-500 text-lg font-black text-white">{branding.logo_url?<img src={branding.logo_url} alt="" className="size-full bg-white object-contain"/>:'Z'}</span>{!collapsed&&<span className="min-w-0 leading-tight"><b className="block truncate text-[14px] text-white">{branding.app_name}</b><small className="block truncate text-[10px] text-slate-400">{branding.company_name}</small></span>}</div>
    <div className="px-3 pt-5"><div className={cn('mb-2 px-2 text-[10px] font-bold uppercase tracking-[.16em] text-slate-500',collapsed&&'text-center')}>{collapsed?'•••':'Workspace'}</div><nav className="space-y-1">{navItems.filter((item)=>permissions.has(item.permission)&&(!('superAdminOnly' in item)||!item.superAdminOnly||user?.roles.some((role)=>role.slug==='super-admin'))).map((item)=>{const Icon=item.icon;return <NavLink key={item.to} to={item.to} title={collapsed?item.label:undefined} end={item.to==='/'} onClick={()=>setMobileOpen(false)} className={({isActive})=>cn('flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-medium transition-colors',isActive?'bg-indigo-500/15 text-white ring-1 ring-inset ring-indigo-400/20':'text-slate-400 hover:bg-white/[.05] hover:text-white',collapsed&&'justify-center px-0')}><Icon size={17}/>{!collapsed&&item.label}</NavLink>})}</nav></div>
    <div className="mt-auto border-t border-white/10 p-3"><button onClick={()=>setCollapsed(!collapsed)} className="hidden w-full items-center justify-center rounded-md p-2 text-slate-500 hover:bg-white/5 md:flex">{collapsed?<ChevronRight size={15}/>:<ChevronLeft size={15}/>}</button></div>
  </aside>
  return <div className="min-h-screen bg-[#f5f7fb] md:flex">
    <div className="hidden md:block">{sidebar}</div>
    {mobileOpen&&<div className="fixed inset-0 z-40 bg-slate-950/40 md:hidden" onClick={()=>setMobileOpen(false)}><div className="h-full" onClick={(e)=>e.stopPropagation()}>{sidebar}</div></div>}
    <div className="min-w-0 flex-1">
      <header className="sticky top-0 z-20 flex h-[62px] items-center justify-between border-b border-slate-200/80 bg-white/95 px-4 backdrop-blur md:h-[74px] md:px-8"><div className="md:hidden"><button className="flex min-w-0 items-center gap-2 font-bold text-slate-900" onClick={()=>setMobileOpen(!mobileOpen)} aria-label="Toggle menu"><span className="grid size-8 shrink-0 place-items-center overflow-hidden rounded-lg bg-indigo-600 text-white">{branding.logo_url?<img src={branding.logo_url} alt="" className="size-full bg-white object-contain"/>:'Z'}</span><span className="max-w-48 truncate">{branding.app_name}</span>{mobileOpen?<X size={18}/>:<Menu size={18}/>}</button></div><div className="hidden min-w-0 md:block"><p className="max-w-2xl truncate text-xs text-slate-400">{branding.company_name}</p><p className="mt-1 text-sm font-semibold text-slate-700">{branding.app_name} workspace</p></div><div className="flex items-center gap-2 md:gap-3"><div className="relative"><button className="relative grid size-9 place-items-center rounded-full text-slate-500 hover:bg-slate-100" aria-label="Notifications" aria-expanded={notificationsOpen} onClick={()=>{setNotificationsOpen(!notificationsOpen);setAccountOpen(false)}}><Bell size={18}/>{notificationCount>0&&<span className="absolute -right-0.5 -top-0.5 grid min-h-4 min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white">{notificationCount>99?'99+':notificationCount}</span>}</button>{notificationsOpen&&<div className="absolute right-0 top-12 z-50 w-80 rounded-xl border border-slate-200 bg-white p-3 shadow-xl"><div className="flex items-center justify-between border-b border-slate-100 px-2 pb-3"><b className="text-sm text-slate-800">Notifications</b><span className="text-[10px] text-slate-400">Live · 1 min refresh</span></div><div className="space-y-1 pt-2">{(notifications.data?.stats.overdue_follow_ups??0)>0&&<Link onClick={()=>setNotificationsOpen(false)} to="/follow-ups" className="block rounded-lg p-3 text-xs hover:bg-rose-50"><b className="text-rose-700">{notifications.data!.stats.overdue_follow_ups} overdue follow-ups</b><span className="mt-1 block text-slate-500">Review clients waiting for a response.</span></Link>}{(notifications.data?.stats.follow_ups_due_today??0)>0&&<Link onClick={()=>setNotificationsOpen(false)} to="/follow-ups" className="block rounded-lg p-3 text-xs hover:bg-amber-50"><b className="text-amber-700">{notifications.data!.stats.follow_ups_due_today} follow-ups due today</b><span className="mt-1 block text-slate-500">Open your follow-up schedule.</span></Link>}{permissions.has('documents.view')&&(notifications.data?.stats.pending_documents??0)>0&&<Link onClick={()=>setNotificationsOpen(false)} to="/documents" className="block rounded-lg p-3 text-xs hover:bg-indigo-50"><b className="text-indigo-700">{notifications.data!.stats.pending_documents} documents pending review</b><span className="mt-1 block text-slate-500">Check recently submitted files.</span></Link>}{notifications.isPending?<p className="p-4 text-center text-xs text-slate-400">Loading notifications…</p>:notificationCount===0&&<p className="p-4 text-center text-xs text-slate-500">You’re all caught up.</p>}</div></div>}</div><div className="relative"><button className="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 hover:bg-slate-50" aria-expanded={accountOpen} onClick={()=>{setAccountOpen(!accountOpen);setNotificationsOpen(false)}}><span className="grid size-9 place-items-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{initials}</span><span className="hidden text-left sm:block"><span className="block max-w-36 truncate text-xs font-semibold text-slate-800">{user?.name}</span><span className="block text-[10px] text-slate-400">{user?.roles[0]?.name??'Team member'}</span></span><ChevronDown size={14} className="text-slate-400"/></button>{accountOpen&&<div className="absolute right-0 top-12 z-50 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl"><div className="border-b border-slate-100 px-3 py-2"><p className="truncate text-xs font-semibold text-slate-800">{user?.name}</p><p className="truncate text-[11px] text-slate-400">{user?.email}</p></div><Link to="/profile" onClick={()=>setAccountOpen(false)} className="mt-1 flex items-center gap-2 rounded-lg px-3 py-2.5 text-xs text-slate-700 hover:bg-slate-50"><UserRound size={15}/>Profile settings</Link><button onClick={()=>logout.mutate()} disabled={logout.isPending} className="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-xs text-rose-600 hover:bg-rose-50"><LogOut size={15}/>{logout.isPending?'Signing out…':'Sign out'}</button></div>}</div></div></header>
      <main className="mx-auto max-w-[1500px] px-4 py-6 sm:px-6 md:px-8 md:py-8"><Outlet/></main>
    </div>
  </div>
}
