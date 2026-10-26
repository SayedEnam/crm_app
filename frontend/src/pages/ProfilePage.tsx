import { useEffect } from 'react'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Save } from 'lucide-react'
import { api, apiMessage } from '@/api/client'
import { useCurrentUser } from '@/components/AuthGate'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import type { User } from '@/types/crm'

const profileSchema=z.object({name:z.string().min(1,'Name is required.').max(120),email:z.string().email('Enter a valid email.'),phone:z.string().max(40),current_password:z.string(),password:z.string(),password_confirmation:z.string()}).refine(v=>!v.password||v.password.length>=12,{path:['password'],message:'Use at least 12 characters.'}).refine(v=>!v.password||v.password===v.password_confirmation,{path:['password_confirmation'],message:'Passwords do not match.'})
type ProfileData=z.infer<typeof profileSchema>
export function ProfilePage(){
  const current=useCurrentUser(),qc=useQueryClient()
  const form=useForm<ProfileData>({resolver:zodResolver(profileSchema),defaultValues:{name:'',email:'',phone:'',current_password:'',password:'',password_confirmation:''}})
  useEffect(()=>{if(current.data)form.reset({name:current.data.name,email:current.data.email,phone:current.data.phone??'',current_password:'',password:'',password_confirmation:''})},[current.data,form])
  const mutation=useMutation({mutationFn:async(values:ProfileData)=>{const data:Record<string,string>={name:values.name,email:values.email,phone:values.phone};if(values.password){data.current_password=values.current_password;data.password=values.password;data.password_confirmation=values.password_confirmation}return (await api.patch<{user:User}>('/profile',data)).data.user},onSuccess:(user)=>{qc.setQueryData(['auth','user'],user);form.reset({name:user.name,email:user.email,phone:user.phone??'',current_password:'',password:'',password_confirmation:''})}})
  return <div className="mx-auto max-w-3xl"><div className="mb-6"><p className="mb-1 text-xs font-semibold uppercase tracking-widest text-indigo-600">Account</p><h1 className="text-2xl font-semibold tracking-tight text-slate-900">My profile</h1><p className="mt-1 text-sm text-slate-500">Keep your contact details and password up to date.</p></div><Card><CardHeader><CardTitle>Personal details</CardTitle></CardHeader><CardContent><form className="space-y-5" onSubmit={form.handleSubmit((values)=>mutation.mutate(values))}><div className="grid gap-4 sm:grid-cols-2">{(['name','email','phone'] as const).map(field=><label className="block" key={field}><span className="mb-1.5 block text-xs font-semibold capitalize text-slate-700">{field==='email'?'Email address':field}</span><Input type={field==='email'?'email':'text'} {...form.register(field)}/>{form.formState.errors[field]&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors[field].message}</span>}</label>)}</div><div className="border-t border-slate-100 pt-5"><h3 className="text-sm font-semibold text-slate-800">Change password</h3><p className="mb-4 mt-1 text-xs text-slate-400">Leave these blank to keep your current password.</p><div className="grid gap-4 sm:grid-cols-3">{(['current_password','password','password_confirmation'] as const).map(field=><label className="block" key={field}><span className="mb-1.5 block text-xs font-semibold text-slate-700">{field==='current_password'?'Current password':field==='password'?'New password':'Confirm new password'}</span><Input type="password" autoComplete={field==='current_password'?'current-password':'new-password'} {...form.register(field)}/>{form.formState.errors[field]&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors[field].message}</span>}</label>)}</div></div>{mutation.error&&<p role="alert" className="rounded-lg bg-rose-50 p-3 text-xs text-rose-700">{apiMessage(mutation.error)}</p>}<Button disabled={mutation.isPending}><Save size={15}/>{mutation.isPending?'Saving…':'Save profile'}</Button></form></CardContent></Card></div>
}
