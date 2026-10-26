import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery } from '@tanstack/react-query'
import { ArrowLeft, CheckCircle2, FilePlus2, FileText, LoaderCircle, ShieldCheck, Trash2, UploadCloud } from 'lucide-react'
import { api, apiMessage, csrfCookie } from '@/api/client'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const intakeSchema = z.object({
  name: z.string().trim().min(2, 'Enter your full name.').max(160),
  phone: z.string().trim().min(6, 'Enter your mobile number.').max(40),
  email: z.string().email('Enter a valid email address.'),
  address: z.string().trim().min(3, 'Enter your address.').max(500),
  service_id: z.string().min(1, 'Choose a service.'),
  notes: z.string().max(5000),
})

type IntakeData = z.infer<typeof intakeSchema>
type DocumentCategory = 'certificate' | 'marksheet' | 'other'
type SupportingDocument = { id: number; category: DocumentCategory; file: File | null }
type Service = { id: number; name: string }
type PortalProfile = { client:{name:string;phone?:string|null;email?:string|null;address?:string|null} }
type SubmissionResult = { message: string; reference: string }
const maxFileSize = 10 * 1024 * 1024
const maxTotalSize = 30 * 1024 * 1024

function validDocument(file: File): boolean {
  return /\.(pdf|jpe?g)$/i.test(file.name) && file.size <= maxFileSize
}

function fileSize(size: number): string {
  return `${(size / (1024 * 1024)).toFixed(size > 1024 * 1024 ? 1 : 2)} MB`
}

export function PublicIntakePage() {
  const [passport, setPassport] = useState<File | null>(null)
  const [supportingDocuments, setSupportingDocuments] = useState<SupportingDocument[]>([])
  const [fileError, setFileError] = useState('')
  const [submitted, setSubmitted] = useState<SubmissionResult | null>(null)
  const form = useForm<IntakeData>({
    resolver: zodResolver(intakeSchema),
    defaultValues: { name: '', phone: '', email: '', address: '', service_id: '', notes: '' },
  })
  const { reset } = form
  const profileQuery = useQuery({ queryKey: ['client-portal-user'], queryFn: async () => (await api.get<PortalProfile>('/client-portal/user')).data })
  useEffect(() => {
    if (profileQuery.data) {
      reset({ name: profileQuery.data.client.name, phone: profileQuery.data.client.phone ?? '', email: profileQuery.data.client.email ?? '', address: profileQuery.data.client.address ?? '', service_id: '', notes: '' })
    }
  }, [profileQuery.data, reset])
  const servicesQuery = useQuery({
    queryKey: ['public-services'],
    queryFn: async () => (await api.get<{ data: Service[] }>('/public/services')).data.data,
  })
  const mutation = useMutation({
    mutationFn: async ({ values, passportFile, documents }: { values: IntakeData; passportFile: File; documents: SupportingDocument[] }) => {
      const body = new FormData()
      body.append('name', values.name)
      body.append('phone', values.phone)
      body.append('email', values.email)
      body.append('address', values.address)
      body.append('service_id', values.service_id)
      if (values.notes) body.append('notes', values.notes)
      body.append('passport', passportFile)
      documents.forEach((document, index) => {
        if (!document.file) return
        body.append(`supporting_documents[${index}][category]`, document.category)
        body.append(`supporting_documents[${index}][file]`, document.file)
      })
      await csrfCookie()
      return (await api.post<SubmissionResult>('/client-portal/applications/intake', body)).data
    },
    onSuccess: (result) => {
      setSubmitted(result)
      setPassport(null)
      setSupportingDocuments([])
      form.reset()
    },
  })

  const onPassportChange = (file: File | undefined) => {
    setFileError('')
    if (!file) {
      setPassport(null)
      return
    }
    if (!validDocument(file)) {
      setPassport(null)
      setFileError('Passport must be a PDF, JPG, or JPEG file no larger than 10 MB.')
      return
    }
    setPassport(file)
  }

  const onSupportingFileChange = (id: number, file: File | undefined) => {
    setFileError('')
    if (!file) return
    if (!validDocument(file)) {
      setSupportingDocuments((documents) => documents.map((document) => document.id === id ? { ...document, file: null } : document))
      setFileError('Documents must be PDF, JPG, or JPEG files no larger than 10 MB each.')
      return
    }
    setSupportingDocuments((documents) => documents.map((document) => document.id === id ? { ...document, file } : document))
  }

  const submit = form.handleSubmit((values) => {
    setFileError('')
    if (!passport) {
      setFileError('Please attach a passport file to continue.')
      return
    }
    if (supportingDocuments.some((document) => !document.file)) {
      setFileError('Choose a file or remove each empty supporting document row.')
      return
    }
    const totalSize = passport.size + supportingDocuments.reduce((total, document) => total + (document.file?.size ?? 0), 0)
    if (totalSize > maxTotalSize) {
      setFileError('The combined size of all files must be 30 MB or less.')
      return
    }
    mutation.mutate({ values, passportFile: passport, documents: supportingDocuments })
  })

  if (submitted) {
    return <main className="grid min-h-screen place-items-center bg-slate-50 px-4 py-10">
      <section className="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-xl shadow-slate-900/[.04] sm:p-12">
        <span className="mx-auto mb-5 grid size-14 place-items-center rounded-full bg-emerald-50 text-emerald-600"><CheckCircle2 size={30}/></span>
        <p className="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Submission received</p>
        <h1 className="mt-3 text-2xl font-semibold text-slate-900">Thank you, we’ll be in touch.</h1>
        <p className="mt-3 text-sm leading-6 text-slate-500">Your request and private documents have been sent to our team. Keep this reference for your records.</p>
        <p className="mx-auto mt-6 inline-flex rounded-xl bg-slate-50 px-5 py-3 font-mono text-sm font-semibold text-slate-800">{submitted.reference}</p>
        <div className="mt-7 flex justify-center gap-3"><Button onClick={() => setSubmitted(null)} variant="secondary">Submit another request</Button><Link className="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white" to="/client/dashboard">Back to dashboard</Link></div>
      </section>
    </main>
  }

  return <main className="min-h-screen bg-[#f6f7fb] px-4 py-8 sm:px-6 sm:py-12">
    <div className="mx-auto max-w-4xl">
      <header className="mb-7 flex items-center justify-between gap-4">
        <div><p className="text-sm font-bold text-slate-900">ZomZom Travels &amp; High Think HR</p><p className="mt-1 text-xs text-slate-500">Client service request</p></div>
        <Link to="/client/dashboard" className="inline-flex items-center gap-2 text-xs font-semibold text-indigo-600 hover:text-indigo-700"><ArrowLeft size={14}/> Client dashboard</Link>
      </header>
      <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-900/[.04]">
        <div className="bg-[#111a2e] px-6 py-8 text-white sm:px-10 sm:py-10">
          <p className="mb-2 text-xs font-bold uppercase tracking-[.2em] text-indigo-300">Get started</p>
          <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Tell us how we can help.</h1>
          <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Choose a service and securely attach your passport and supporting documents. This request will be added to your client account.</p>
        </div>
        <form className="space-y-8 p-6 sm:p-10" onSubmit={submit}>
          <section>
            <h2 className="text-sm font-semibold text-slate-900">Your information</h2>
            <div className="mt-4 grid gap-4 sm:grid-cols-2">
              <label className="block"><span className="mb-1.5 block text-xs font-semibold text-slate-700">Full name <b className="text-rose-500">*</b></span><Input autoComplete="name" {...form.register('name')}/>{form.formState.errors.name&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors.name.message}</span>}</label>
              <label className="block"><span className="mb-1.5 block text-xs font-semibold text-slate-700">Mobile number <b className="text-rose-500">*</b></span><Input type="tel" autoComplete="tel" {...form.register('phone')}/>{form.formState.errors.phone&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors.phone.message}</span>}</label>
              <label className="block"><span className="mb-1.5 block text-xs font-semibold text-slate-700">Email <b className="text-rose-500">*</b></span><Input type="email" autoComplete="email" {...form.register('email')}/>{form.formState.errors.email&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors.email.message}</span>}</label>
              <label className="block"><span className="mb-1.5 block text-xs font-semibold text-slate-700">Address <b className="text-rose-500">*</b></span><Input autoComplete="street-address" {...form.register('address')}/>{form.formState.errors.address&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors.address.message}</span>}</label>
              <label className="block"><span className="mb-1.5 block text-xs font-semibold text-slate-700">Service needed <b className="text-rose-500">*</b></span><select className="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100" {...form.register('service_id')}><option value="">Choose a service</option>{(servicesQuery.data ?? []).map((service) => <option key={service.id} value={service.id}>{service.name}</option>)}</select>{servicesQuery.error&&<span className="mt-1 block text-xs text-rose-600">Could not load services. Refresh and try again.</span>}{form.formState.errors.service_id&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors.service_id.message}</span>}</label>
              <label className="block sm:col-span-2"><span className="mb-1.5 block text-xs font-semibold text-slate-700">How can we help? <span className="font-normal text-slate-400">(optional)</span></span><textarea className="min-h-28 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100" placeholder="Add any details that will help us understand your request." {...form.register('notes')}/>{form.formState.errors.notes&&<span className="mt-1 block text-xs text-rose-600">{form.formState.errors.notes.message}</span>}</label>
            </div>
          </section>

          <section className="border-t border-slate-100 pt-7">
            <div className="flex items-start gap-3"><span className="grid size-9 shrink-0 place-items-center rounded-lg bg-indigo-50 text-indigo-600"><ShieldCheck size={18}/></span><div><h2 className="text-sm font-semibold text-slate-900">Passport</h2><p className="mt-1 text-xs text-slate-500">Required. PDF, JPG, or JPEG, up to 10 MB.</p></div></div>
            <label className="mt-4 flex cursor-pointer flex-col items-center rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-4 py-6 text-center transition hover:border-indigo-300 hover:bg-indigo-50/30">
              <UploadCloud size={22} className="text-indigo-500"/><span className="mt-2 text-sm font-semibold text-slate-700">{passport ? passport.name : 'Choose your passport file'}</span><span className="mt-1 text-xs text-slate-400">PDF, JPG, JPEG · maximum 10 MB</span>
              <input className="sr-only" type="file" accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg" onChange={(event) => onPassportChange(event.target.files?.[0])}/>
            </label>
            {passport&&<p className="mt-2 text-xs text-slate-500">Selected · {fileSize(passport.size)}</p>}
          </section>

          <section className="border-t border-slate-100 pt-7">
            <div className="flex flex-wrap items-center justify-between gap-3"><div className="flex items-start gap-3"><span className="grid size-9 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-600"><FileText size={18}/></span><div><h2 className="text-sm font-semibold text-slate-900">Supporting documents <span className="font-normal text-slate-400">(optional)</span></h2><p className="mt-1 text-xs text-slate-500">Add certificates, marksheets, or other documents.</p></div></div><Button type="button" variant="secondary" size="sm" disabled={supportingDocuments.length>=8} onClick={() => setSupportingDocuments((documents) => [...documents, { id: Date.now()+Math.random(), category: 'certificate', file: null }])}><FilePlus2 size={14}/>Add document</Button></div>
            {supportingDocuments.length>0&&<div className="mt-4 space-y-3">{supportingDocuments.map((document) => <div key={document.id} className="grid gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-[150px_1fr_auto] sm:items-center"><select aria-label="Document category" className="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100" value={document.category} onChange={(event) => setSupportingDocuments((documents) => documents.map((entry) => entry.id===document.id?{...entry,category:event.target.value as DocumentCategory}:entry))}><option value="certificate">Certificate</option><option value="marksheet">Marksheet</option><option value="other">Other</option></select><label className="flex min-w-0 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-xs text-slate-600 hover:bg-slate-50"><UploadCloud size={15} className="shrink-0 text-indigo-500"/><span className="truncate">{document.file?.name??'Choose PDF, JPG, or JPEG'}</span><input className="sr-only" type="file" accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg" onChange={(event) => onSupportingFileChange(document.id,event.target.files?.[0])}/></label><Button type="button" aria-label="Remove document" variant="ghost" size="icon" className="text-slate-400 hover:text-rose-600" onClick={() => setSupportingDocuments((documents) => documents.filter((entry) => entry.id!==document.id))}><Trash2 size={16}/></Button></div>)}</div>}
            <p className="mt-3 text-xs text-slate-400">Up to 8 files, 10 MB each. Combined upload limit: 30 MB.</p>
          </section>

          {fileError&&<p role="alert" className="rounded-lg bg-rose-50 p-3 text-xs text-rose-700">{fileError}</p>}
          {mutation.error&&<p role="alert" className="rounded-lg bg-rose-50 p-3 text-xs text-rose-700">{apiMessage(mutation.error)}</p>}
          <div className="border-t border-slate-100 pt-6"><Button className="w-full sm:w-auto" disabled={mutation.isPending||servicesQuery.isPending}>{mutation.isPending?<><LoaderCircle className="animate-spin" size={16}/>Submitting…</>:<>Submit information <UploadCloud size={16}/></>}</Button><p className="mt-3 text-xs leading-5 text-slate-400">Your passport and supporting documents are stored privately and can only be viewed by authorized CRM staff.</p></div>
        </form>
      </div>
    </div>
  </main>
}
