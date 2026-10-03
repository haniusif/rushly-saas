'use client';
import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/cn';

const button = cva(
  'relative inline-flex items-center justify-center gap-2 font-medium tracking-tight transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60 focus-visible:ring-offset-2 focus-visible:ring-offset-bg disabled:opacity-50 disabled:pointer-events-none whitespace-nowrap',
  {
    variants: {
      variant: {
        primary:
          'text-white bg-gradient-to-b from-primary-500 to-primary-700 shadow-[0_1px_0_0_rgba(255,255,255,0.25)_inset,0_10px_30px_-8px_rgba(37,99,235,0.55)] hover:shadow-[0_1px_0_0_rgba(255,255,255,0.3)_inset,0_16px_40px_-8px_rgba(37,99,235,0.75)] hover:-translate-y-[1px]',
        secondary:
          'text-white glass hover:bg-white/[0.06] border border-white/10',
        ghost:
          'text-white/80 hover:text-white hover:bg-white/[0.04]',
        outline:
          'text-white border border-white/15 hover:border-white/30 hover:bg-white/[0.03]',
      },
      size: {
        sm: 'h-9 px-4 text-sm rounded-full',
        md: 'h-11 px-5 text-sm rounded-full',
        lg: 'h-12 px-6 text-[15px] rounded-full',
        xl: 'h-14 px-7 text-base rounded-full',
      },
    },
    defaultVariants: { variant: 'primary', size: 'md' },
  },
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof button> {
  asChild?: boolean;
}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, children, ...props }, ref) => (
    <button ref={ref} className={cn(button({ variant, size }), className)} {...props}>
      <span className="relative z-10 inline-flex items-center gap-2">{children}</span>
    </button>
  ),
);
Button.displayName = 'Button';
