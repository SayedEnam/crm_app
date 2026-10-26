import { api, csrfCookie } from './client'
import type { User } from '@/types/crm'
export const authApi = {
  async login(input: { email: string; password: string; remember: boolean }) { await csrfCookie(); return (await api.post<{ user: User }>('/auth/login', input)).data.user },
  async logout() { await api.post('/auth/logout') },
  async me() { return (await api.get<User>('/user')).data },
  async forgotPassword(email: string) { await csrfCookie(); return (await api.post<{ message: string }>('/auth/forgot-password', { email })).data },
  async resetPassword(input: { token: string; email: string; password: string; password_confirmation: string }) { await csrfCookie(); return (await api.post<{ message: string }>('/auth/reset-password', input)).data },
}
