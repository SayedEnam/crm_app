export type Permission = { id: number; name: string; slug: string }
export type Role = { id?: number; name: string; slug: string; permissions?: Permission[] }
export type User = { id: number; name: string; email: string; phone?: string | null; is_active: boolean; assigned_manager_id?: number | null; roles: Role[] }
export type Client = { id: number; name: string; email?: string | null; phone?: string | null; company?: string | null; country?: string | null; address?: string | null; status: string; notes?: string | null; assigned_user_id?: number | null; created_at: string }
export type Service = { id: number; name: string; slug: string; description?: string | null; is_active: boolean }
export type Application = { id: number; client_id: number; client?: Pick<Client,'id'|'name'>; service_id: number; service?: Pick<Service,'id'|'name'|'slug'>; reference?: string | null; status: string; started_at?: string | null; completed_at?: string | null; notes?: string | null; created_at: string }
export type FollowUp = { id: number; client_id: number; client?: Pick<Client,'id'|'name'>; application_id?: number | null; due_at: string; status: string; notes?: string | null }
export type Payment = { id: number; client_id: number; client?: Pick<Client,'id'|'name'>; application_id?: number | null; amount: string; currency: string; status: string; method?: string | null; reference?: string | null; paid_at?: string | null; notes?: string | null; created_at: string }
export type DocumentRecord = { id: number; application_id: number; name: string; mime_type?: string | null; status: string; notes?: string | null; created_at: string; download_url: string }
export type Page<T> = { data: T[]; links?: { next?: string | null; prev?: string | null }; meta?: { current_page: number; last_page: number; total: number } }
export type Dashboard = {
  filters: { from: string; to: string }
  stats: Record<'total_clients'|'new_clients_this_month'|'total_leads'|'active_applications'|'pending_documents'|'follow_ups_due_today'|'overdue_follow_ups'|'outstanding_payments'|'total_collections'|'completed_applications', number | string | null>
  recent_clients: Client[]
  recent_applications: Application[]
  upcoming_follow_ups: FollowUp[]
  charts: { registrations: { month: string; total: number }[]; application_statuses: { status: string; total: number }[]; services: { service: string; total: number }[] }
}
