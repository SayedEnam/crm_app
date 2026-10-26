import axios from 'axios'
const apiBase = import.meta.env.VITE_API_URL || '/api/v1'
const backendBase = import.meta.env.VITE_BACKEND_URL || ''
export const api = axios.create({ baseURL: apiBase, withCredentials: true, withXSRFToken: true, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
export async function csrfCookie() { await axios.get(backendBase + '/sanctum/csrf-cookie', { withCredentials: true }) }
export function apiMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined
    if (data?.errors) return Object.values(data.errors).flat()[0] ?? 'Please check the form.'
    return data?.message ?? 'The request could not be completed.'
  }
  return error instanceof Error ? error.message : 'Something went wrong.'
}
