import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Building2, ImagePlus, Save, Trash2 } from 'lucide-react'
import { api, apiMessage, csrfCookie } from '@/api/client'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import type { Branding } from '@/hooks/useBranding'

export function BrandingSettingsPage() {
  const queryClient = useQueryClient()
  const query = useQuery({ queryKey: ['branding'], queryFn: async () => (await api.get<{ data: Branding }>('/settings/branding')).data.data })
  const [appName, setAppName] = useState('')
  const [companyName, setCompanyName] = useState('')
  const [logo, setLogo] = useState<File | null>(null)
  const [removeLogo, setRemoveLogo] = useState(false)
  const [preview, setPreview] = useState<string | null>(null)
  const [notice, setNotice] = useState('')
  const [error, setError] = useState('')

  useEffect(() => {
    if (!query.data) return
    setAppName(query.data.app_name)
    setCompanyName(query.data.company_name)
  }, [query.data])

  useEffect(() => {
    if (!logo) {
      setPreview(null)
      return
    }
    const url = URL.createObjectURL(logo)
    setPreview(url)
    return () => URL.revokeObjectURL(url)
  }, [logo])

  const save = useMutation({
    mutationFn: async () => {
      const body = new FormData()
      body.append('app_name', appName)
      body.append('company_name', companyName)
      body.append('remove_logo', removeLogo ? '1' : '0')
      if (logo) body.append('logo', logo)
      await csrfCookie()
      return (await api.post<{ message: string; data: Branding }>('/settings/branding', body)).data
    },
    onSuccess: (result) => {
      setNotice(result.message)
      setError('')
      setLogo(null)
      setRemoveLogo(false)
      queryClient.setQueryData(['branding'], result.data)
      queryClient.invalidateQueries({ queryKey: ['branding'] })
    },
    onError: (reason) => { setError(apiMessage(reason)); setNotice('') },
  })

  if (query.isPending) return <p className="py-12 text-center text-sm text-slate-500">Loading branding settings…</p>
  if (query.error) return <p role="alert" className="rounded-lg bg-rose-50 p-4 text-sm text-rose-700">{apiMessage(query.error)}</p>

  const currentLogo = preview ?? (removeLogo ? null : query.data?.logo_url ?? null)

  return <div className="mx-auto max-w-3xl">
    <div className="mb-6"><p className="mb-1 text-xs font-semibold uppercase tracking-widest text-slate-500">Super Admin</p><h1 className="text-2xl font-semibold tracking-tight text-slate-900">App &amp; company branding</h1><p className="mt-1 text-sm text-slate-500">Set the names and logo shown across the CRM, sign-in page, and client portal.</p></div>
    <Card><CardHeader><CardTitle className="flex items-center gap-2"><Building2 size={18}/>Workspace identity</CardTitle></CardHeader><CardContent>
      <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); setNotice(''); setError(''); save.mutate() }}>
        <div className="grid gap-4 sm:grid-cols-2"><label className="block text-xs font-semibold text-slate-700">App name<Input className="mt-1.5" required maxLength={100} value={appName} onChange={(event) => setAppName(event.target.value)}/><span className="mt-1 block font-normal text-slate-400">Used in the app header and browser-facing pages.</span></label><label className="block text-xs font-semibold text-slate-700">Company name<Input className="mt-1.5" required maxLength={180} value={companyName} onChange={(event) => setCompanyName(event.target.value)}/><span className="mt-1 block font-normal text-slate-400">Shown as the organization behind this workspace.</span></label></div>
        <div className="rounded-xl border border-dashed border-slate-300 p-5"><div className="flex flex-wrap items-center gap-4"><div className="grid size-20 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">{currentLogo ? <img src={currentLogo} alt="Company logo preview" className="size-full object-contain p-1"/> : <ImagePlus className="text-slate-400"/>}</div><div className="min-w-0 flex-1"><p className="text-sm font-semibold text-slate-800">Company logo</p><p className="mt-1 text-xs text-slate-500">PNG, JPG or WEBP · maximum 2 MB. A square or horizontal logo works best.</p><div className="mt-3 flex flex-wrap gap-2"><label className="inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50"><ImagePlus size={14}/>{logo ? 'Choose another image' : 'Upload logo'}<input className="sr-only" type="file" accept="image/png,image/jpeg,image/webp" onChange={(event) => { const file = event.target.files?.[0] ?? null; setLogo(file); if (file) setRemoveLogo(false) }}/></label>{query.data?.logo_url && <Button type="button" variant="secondary" size="sm" onClick={() => { setLogo(null); setRemoveLogo(true) }}><Trash2 size={14}/>Remove current logo</Button>}</div></div></div></div>
        {notice && <p role="status" className="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</p>}{error && <p role="alert" className="rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{error}</p>}
        <div className="border-t border-slate-100 pt-5"><Button disabled={save.isPending}><Save size={15}/>{save.isPending ? 'Saving…' : 'Save branding'}</Button></div>
      </form>
    </CardContent></Card>
  </div>
}
