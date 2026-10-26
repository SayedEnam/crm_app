import type { HTMLAttributes } from 'react'
import { cn } from '@/lib/utils'
export function Badge({ className, ...props }: HTMLAttributes<HTMLSpanElement>) { return <span className={cn('inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold capitalize text-slate-600', className)} {...props} /> }
