import type { InputHTMLAttributes } from 'react';

type FormFieldProps = InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    error?: string;
    hint?: string;
};

/** Accessible text input with consistent help, error, focus, and disabled states. */
export default function FormField({ className = '', label, error, hint, id, ...props }: FormFieldProps) {
    const describedBy = [hint ? `${id}-hint` : null, error ? `${id}-error` : null].filter(Boolean).join(' ') || undefined;

    return (
        <label htmlFor={id} className="flex flex-col gap-2 text-sm font-medium">
            {label}
            <input
                id={id}
                {...props}
                className={`rounded-lg border bg-background px-3.5 py-3 text-sm text-text-primary outline-none transition placeholder:text-text-secondary/60 disabled:cursor-not-allowed disabled:bg-surface-elevated disabled:opacity-60 ${error ? 'border-danger focus:border-danger focus:ring-2 focus:ring-danger/20' : 'border-border focus:border-ct focus:ring-2 focus:ring-ct/20'} ${className}`}
                aria-invalid={Boolean(error)}
                aria-describedby={describedBy}
            />
            {hint && !error && <span id={`${id}-hint`} className="text-xs font-normal text-text-secondary">{hint}</span>}
            {error && <span id={`${id}-error`} className="text-xs font-normal text-red-400">{error}</span>}
        </label>
    );
}
