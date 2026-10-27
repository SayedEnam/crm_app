import { useQuery } from '@tanstack/react-query'
import { api } from '@/api/client'

export type Branding = { app_name: string; company_name: string; logo_url: string | null }

const defaultBranding: Branding = {
  app_name: 'ZomZom CRM',
  company_name: 'ZomZom Travels & High Think HR Consultancy',
  logo_url: null,
}

export function useBranding() {
  const query = useQuery({
    queryKey: ['branding'],
    queryFn: async () => (await api.get<{ data: Branding }>('/public/branding')).data.data,
    staleTime: 60_000,
    retry: false,
  })

  return query.data ?? defaultBranding
}
