import { forwardRef, type ButtonHTMLAttributes } from 'react'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'
const styles = cva('inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg text-sm font-semibold transition-colors disabled:pointer-events-none disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-500', {
  variants: {
    variant: { default: 'bg-slate-950 text-white shadow-sm hover:bg-slate-800', secondary: 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50', ghost: 'text-slate-600 hover:bg-slate-100', destructive: 'bg-rose-600 text-white hover:bg-rose-700', link: 'text-slate-900 underline-offset-4 hover:underline' },
    size: { default: 'h-10 px-4 py-2', sm: 'h-8 rounded-md px-3 text-xs', icon: 'size-9 p-0' },
  }, defaultVariants: { variant: 'default', size: 'default' },
})
export type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & VariantProps<typeof styles>
export const Button = forwardRef<HTMLButtonElement, ButtonProps>(({ className, variant, size, ...props }, ref) => <button ref={ref} className={cn(styles({ variant, size }), className)} {...props} />)
Button.displayName = 'Button'
