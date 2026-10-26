import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) { return twMerge(clsx(inputs)) }
export function formatMoney(value: number | string | null | undefined) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'BDT', maximumFractionDigits: 0 }).format(Number(value ?? 0))
}
export function formatDate(value?: string | null) {
  return value ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(value)) : '—'
}
