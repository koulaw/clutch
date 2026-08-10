import type { InputHTMLAttributes } from 'react';

type FormFieldProps = InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    error?: string;
};

export default function FormField({ label, error, id, ...props }: FormFieldProps) {
    return (
        <label htmlFor={id} className="flex flex-col gap-2 text-sm font-medium">
            {label}
            <input
                id={id}
                {...props}
                className="rounded-lg border border-border bg-background px-3.5 py-3 text-sm text-text-primary outline-none transition placeholder:text-text-secondary/60 focus:border-ct focus:ring-2 focus:ring-ct/20"
                aria-invalid={Boolean(error)}
                aria-describedby={error ? `${id}-error` : undefined}
            />
            {error && <span id={`${id}-error`} className="text-xs font-normal text-red-400">{error}</span>}
        </label>
    );
}
