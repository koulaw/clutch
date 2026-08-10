import type { HTMLAttributes, PropsWithChildren } from 'react';

type CardProps = PropsWithChildren<HTMLAttributes<HTMLElement> & {
    elevated?: boolean;
}>;

/** Surface container for grouping related content. */
export default function Card({ children, className = '', elevated = false, ...props }: CardProps) {
    return (
        <article
            {...props}
            className={`rounded-2xl border border-border p-5 ${elevated ? 'bg-surface-raised shadow-xl shadow-black/20' : 'bg-surface'} ${className}`}
        >
            {children}
        </article>
    );
}
