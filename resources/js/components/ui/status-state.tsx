import type { ReactNode } from 'react';
import Button from '@/components/ui/button';

type StatusTone = 'empty' | 'success' | 'error';

const toneStyles: Record<StatusTone, string> = {
    empty: 'border-border bg-surface text-text-secondary',
    success: 'border-success/40 bg-success/10 text-success-light',
    error: 'border-danger/40 bg-danger/10 text-danger-light',
};

type StatusStateProps = {
    title: string;
    description?: string;
    tone?: StatusTone;
    action?: { label: string; onClick: () => void };
    icon?: ReactNode;
    compact?: boolean;
    className?: string;
};

/** Empty, success, and error feedback state with an optional recovery action. */
export function StatusState({ action, className = '', compact = false, description, icon, title, tone = 'empty' }: StatusStateProps) {
    const role = tone === 'error' ? 'alert' : 'status';

    return (
        <section role={role} className={`flex flex-col items-center rounded-2xl border px-6 text-center ${compact ? 'gap-1 py-4' : 'gap-3 py-10'} ${toneStyles[tone]} ${className}`}>
            {icon && <span aria-hidden="true">{icon}</span>}
            <div className="flex flex-col gap-1">
                <h2 className="font-semibold text-text-primary">{title}</h2>
                {description && <p className="max-w-md text-sm leading-6">{description}</p>}
            </div>
            {action && <Button variant="secondary" size="sm" onClick={action.onClick}>{action.label}</Button>}
        </section>
    );
}

/** Animated placeholder matching the card rhythm while content is loading. */
export function LoadingState({ label = 'Loading…', rows = 3 }: { label?: string; rows?: number }) {
    return (
        <section role="status" aria-label={label} className="rounded-2xl border border-border bg-surface p-5">
            <span className="sr-only">{label}</span>
            <div className="flex animate-pulse flex-col gap-4" aria-hidden="true">
                <div className="h-4 w-2/5 rounded bg-surface-raised" />
                {Array.from({ length: rows }, (_, index) => <div key={index} className="h-3 rounded bg-surface-elevated last:w-3/4" />)}
            </div>
        </section>
    );
}
