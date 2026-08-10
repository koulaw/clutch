import type { ButtonHTMLAttributes } from 'react';

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';
export type ButtonSize = 'sm' | 'md' | 'lg';

const variants: Record<ButtonVariant, string> = {
    primary: 'bg-accent text-white hover:bg-accent-hover focus-visible:outline-accent',
    secondary: 'border border-border bg-surface-elevated text-text-primary hover:border-ct-dark hover:bg-surface-raised focus-visible:outline-ct',
    ghost: 'text-text-secondary hover:bg-surface-elevated hover:text-text-primary focus-visible:outline-ct',
    danger: 'border border-danger/40 bg-danger/10 text-danger-light hover:bg-danger/20 focus-visible:outline-danger',
};

const sizes: Record<ButtonSize, string> = {
    sm: 'min-h-9 px-3 py-2 text-xs',
    md: 'min-h-11 px-4 py-2.5 text-sm',
    lg: 'min-h-12 px-5 py-3 text-sm',
};

/**
 * Returns the shared button styles for buttons and link-based actions.
 */
export function buttonClassName(variant: ButtonVariant = 'primary', size: ButtonSize = 'md', className = ''): string {
    return `inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ${variants[variant]} ${sizes[size]} ${className}`;
}

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: ButtonVariant;
    size?: ButtonSize;
    loading?: boolean;
};

/** Primary action primitive. Use `loading` to expose progress to assistive technology. */
export default function Button({ children, className, disabled, loading = false, size = 'md', variant = 'primary', type = 'button', ...props }: ButtonProps) {
    return (
        <button
            {...props}
            type={type}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            className={buttonClassName(variant, size, className)}
        >
            {loading && <span className="size-4 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true" />}
            {children}
        </button>
    );
}
