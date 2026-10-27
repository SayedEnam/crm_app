import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, BriefcaseBusiness, Eye, EyeOff, LockKeyhole, Mail } from 'lucide-react'
import { authApi } from '@/api/auth'
import { apiMessage } from '@/api/client'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useBranding } from '@/hooks/useBranding'

const loginSchema=z.object({email:z.string().email('Enter a valid email.'),password:z.string().min(1,'Password is required.'),remember:z.boolean()})
type LoginData=z.infer<typeof loginSchema>
const demoRoleAccounts=[
  {role:'Super Admin',email:'demo.super-admin@example.com'},
  {role:'Admin',email:'demo.admin@example.com'},
  {role:'Manager',email:'demo.manager@example.com'},
  {role:'Staff',email:'demo.staff@example.com'},
  {role:'Accountant',email:'demo.accountant@example.com'},
  {role:'Viewer',email:'demo.viewer@example.com'},
]
const demoRolePassword='DemoRole@2026!'
function AuthCard({title,subtitle,children}:{title:string;subtitle:string;children:React.ReactNode}) {
  const branding=useBranding()
  const brandMark=branding.logo_url?<img src={branding.logo_url} alt="" className="size-full rounded-xl bg-white object-contain p-1"/>:<BriefcaseBusiness size={23}/>
  return <div className="min-h-screen bg-[#111a2e] lg:grid lg:grid-cols-[1.05fr_.95fr]"><section className="relative hidden overflow-hidden p-14 text-white lg:flex lg:flex-col lg:justify-between"><div className="absolute -left-32 -top-32 size-[520px] rounded-full border border-white/[.07]"/><div className="absolute -left-12 -top-12 size-[280px] rounded-full border border-white/[.08]"/><div className="relative flex items-center gap-3"><span className="grid size-11 place-items-center overflow-hidden rounded-xl bg-indigo-500 text-white">{brandMark}</span><div className="min-w-0"><b className="block truncate text-lg">{branding.app_name}</b><p className="max-w-lg truncate text-[10px] tracking-wide text-slate-400">{branding.company_name}</p></div></div><div className="relative max-w-xl"><p className="mb-5 text-xs font-bold uppercase tracking-[.22em] text-indigo-300">One workspace. Every client journey.</p><h1 className="text-5xl font-semibold leading-[1.1] tracking-tight">Build trust.<br/><span className="text-indigo-300">Move people forward.</span></h1><p className="mt-6 max-w-md text-sm leading-7 text-slate-400">Manage travel, admissions, documentation, HR consultancy and the relationships that keep every service moving.</p></div><p className="relative text-xs text-slate-500">{branding.company_name}</p></section><section className="grid min-h-screen place-items-center bg-[#f6f7fb] px-5 py-12"><div className="w-full max-w-[420px]"><div className="mb-8 text-center lg:hidden"><b className="text-lg text-slate-900">{branding.app_name}</b><p className="mt-1 text-xs text-slate-400">{branding.company_name}</p></div><div className="rounded-2xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-900/[.04] sm:p-9"><div className="mb-7"><h2 className="text-2xl font-semibold tracking-tight text-slate-900">{title}</h2><p className="mt-2 text-sm text-slate-500">{subtitle}</p></div>{children}</div><p className="mt-6 text-center text-[11px] text-slate-400">Secure access · Your session is protected</p></div></section></div>
}
export function LoginPage() {
  const navigate=useNavigate(),qc=useQueryClient(),[show,setShow]=useState(false)
  const form=useForm<LoginData>({resolver:zodResolver(loginSchema),defaultValues:{email:'',password:'',remember:false}})
  const fillDemoAccount=(email:string)=>{form.setValue('email',email,{shouldValidate:true});form.setValue('password',demoRolePassword,{shouldValidate:true});mutation.reset()}
  const mutation=useMutation({mutationFn:authApi.login,onSuccess:(user)=>{qc.setQueryData(['auth','user'],user);navigate('/',{replace:true})}})
  return <AuthCard title="Welcome back" subtitle="Sign in to continue to your workspace."><form className="space-y-4" onSubmit={form.handleSubmit((data)=>mutation.mutate(data))}>
    <label className="block"><span className="mb-1.5 block text-xs font-semibold text-slate-700">Email address</span><span className="relative block"><Mail size={16} className="absolute left-3 top-3 text-slate-400"/><Input className="pl-9" autoComplete="username" type="email" {...form.register('email')}/></span>{form.formState.errors.email&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors.email.message}</span>}</label>
    <label className="block"><span className="mb-1.5 block text-xs font-semibold text-slate-700">Password</span><span className="relative block"><LockKeyhole size={16} className="absolute left-3 top-3 text-slate-400"/><Input className="pl-9 pr-10" autoComplete="current-password" type={show?'text':'password'} {...form.register('password')}/><button type="button" className="absolute right-3 top-2.5 text-slate-400" onClick={()=>setShow(!show)} aria-label={show?'Hide password':'Show password'}>{show?<EyeOff size={17}/>:<Eye size={17}/>}</button></span>{form.formState.errors.password&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors.password.message}</span>}</label>
    <div className="flex items-center justify-between"><label className="flex items-center gap-2 text-xs text-slate-500"><input type="checkbox" className="accent-indigo-600" {...form.register('remember')}/>Keep me signed in</label><Link className="text-xs font-semibold text-indigo-600 hover:text-indigo-700" to="/forgot-password">Forgot password?</Link></div>
    {mutation.error&&<p role="alert" className="rounded-lg bg-rose-50 p-3 text-xs text-rose-700">{apiMessage(mutation.error)}</p>}
    <Button className="w-full" disabled={mutation.isPending}>{mutation.isPending?'Signing in…':<>Sign in <ArrowRight size={16}/></>}</Button>
    <p className="pt-2 text-center text-xs text-slate-500">New client? <Link className="font-semibold text-indigo-600 hover:text-indigo-700" to="/apply">Submit your information</Link></p>
    <p className="text-center text-xs text-slate-500">Already have portal access? <Link className="font-semibold text-indigo-600" to="/client/login">Client sign in</Link></p>
    <section aria-label="Staff demo accounts" className="rounded-xl border border-indigo-100 bg-indigo-50/70 p-4"><div className="flex items-center justify-between gap-2"><p className="text-xs font-bold uppercase tracking-wide text-indigo-900">Explore staff dashboards</p><span className="rounded-full bg-white px-2 py-1 text-[10px] font-semibold text-indigo-700">Read-only demo</span></div><div className="mt-3 grid grid-cols-2 gap-2">{demoRoleAccounts.map((account)=><button key={account.role} type="button" onClick={()=>fillDemoAccount(account.email)} className="rounded-lg border border-indigo-100 bg-white px-2.5 py-2 text-left text-xs font-semibold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50">{account.role}<span className="mt-1 block truncate text-[10px] font-normal text-slate-500">{account.email}</span></button>)}</div><p className="mt-3 text-[11px] leading-4 text-slate-600">Password for all: <span className="select-all font-semibold text-slate-800">{demoRolePassword}</span></p><p className="mt-1.5 text-[10px] leading-4 text-slate-500">Demo accounts only see fictional assigned records. Changes to the profile and CRM data are disabled.</p></section>
  </form></AuthCard>
}
const emailSchema=z.object({email:z.string().email('Enter a valid email.')})
type EmailData=z.infer<typeof emailSchema>
export function ForgotPasswordPage() {
  const [notice,setNotice]=useState(''),form=useForm<EmailData>({resolver:zodResolver(emailSchema)})
  const mutation=useMutation({mutationFn:authApi.forgotPassword,onSuccess:(result)=>setNotice(result.message)})
  return <AuthCard title="Reset your password" subtitle="Enter your account email. If it matches an account, we’ll send a reset link."><form className="space-y-4" onSubmit={form.handleSubmit((value)=>mutation.mutate(value.email))}><label className="block"><span className="mb-1.5 block text-xs font-semibold text-slate-700">Email address</span><Input type="email" autoComplete="email" {...form.register('email')}/>{form.formState.errors.email&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors.email.message}</span>}</label>{notice&&<p className="rounded-lg bg-emerald-50 p-3 text-xs text-emerald-700">{notice}</p>}{mutation.error&&<p className="text-xs text-rose-600">{apiMessage(mutation.error)}</p>}<Button className="w-full" disabled={mutation.isPending}>Send reset link</Button><Link className="block text-center text-xs text-indigo-600" to="/login">Back to sign in</Link></form></AuthCard>
}
const resetSchema=z.object({password:z.string().min(12,'Use at least 12 characters.'),password_confirmation:z.string().min(1,'Confirm your password.')}).refine((value)=>value.password===value.password_confirmation,{path:['password_confirmation'],message:'Passwords do not match.'})
type ResetData=z.infer<typeof resetSchema>
export function ResetPasswordPage() {
  const [params]=useSearchParams(),navigate=useNavigate(),[notice,setNotice]=useState('')
  const form=useForm<ResetData>({resolver:zodResolver(resetSchema)})
  const mutation=useMutation({mutationFn:async(data:ResetData)=>authApi.resetPassword({token:params.get('token')??'',email:params.get('email')??'',...data}),onSuccess:(result)=>{setNotice(result.message);setTimeout(()=>navigate('/login'),1800)}})
  return <AuthCard title="Choose a new password" subtitle="Create a strong password with at least 12 characters."><form className="space-y-4" onSubmit={form.handleSubmit((data)=>mutation.mutate(data))}>{(['password','password_confirmation'] as const).map((field)=><label key={field} className="block"><span className="mb-1.5 block text-xs font-semibold text-slate-700">{field==='password'?'New password':'Confirm password'}</span><Input type="password" autoComplete="new-password" {...form.register(field)}/>{form.formState.errors[field]&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors[field].message}</span>}</label>)}{notice&&<p className="rounded-lg bg-emerald-50 p-3 text-xs text-emerald-700">{notice}</p>}{mutation.error&&<p className="text-xs text-rose-600">{apiMessage(mutation.error)}</p>}<Button className="w-full" disabled={mutation.isPending}>Save new password</Button></form></AuthCard>
}
