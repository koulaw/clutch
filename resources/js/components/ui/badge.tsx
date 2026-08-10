import type { HTMLAttributes, PropsWithChildren } from 'react';

type BadgeTone = 'neutral' | 'ct' | 't' | 'success' | 'danger';

const tones: Record<BadgeTone, string> = {
    neutral: 'border-border bg-surface-elevated text-text-secondary',
    ct: 'border-ct-dark bg-ct-dark/30 text-ct-light',
    t: 'border-t-dark bg-t-dark/20 text-t',
    success: 'border-success/40 bg-success/10 text-success-light',
    danger: 'border-danger/40 bg-danger/10 text-danger-light',
};

/** Compact semantic label; choose a tone that matches the label meaning. */
export default function Badge({ children, className = '', tone = 'neutral', ...props }: PropsWithChildren<HTMLAttributes<HTMLSpanElement> & { tone?: BadgeTone }>) {
    return <span {...props} className={`inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-medium ${tones[tone]} ${className}`}>{children}</span>;
}
