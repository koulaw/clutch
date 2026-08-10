import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { buttonClassName } from '@/components/ui/button';

type NavigationAction = {
    label: string;
    href: string;
    method?: 'get' | 'post';
    variant?: 'primary' | 'secondary' | 'ghost';
};

/** Shared brand navigation for public and authenticated layouts. */
export default function Navigation({ actions, homeLabel, trailing }: { actions: NavigationAction[]; homeLabel: string; trailing?: ReactNode }) {
    return (
        <header className="flex items-center justify-between gap-4 border-b border-border/70 py-5">
            <Link href="/" aria-label={homeLabel} className="shrink-0 focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">
                <img src="/images/brand/logo-dark.svg" alt="Clutch." className="h-10 w-auto sm:h-11" />
            </Link>
            <nav aria-label={homeLabel} className="flex items-center gap-1 sm:gap-2">
                {actions.map((action) => (
                    <Link key={`${action.method ?? 'get'}-${action.href}`} href={action.href} method={action.method} as={action.method === 'post' ? 'button' : 'a'} className={buttonClassName(action.variant ?? 'ghost', 'sm')}>
                        {action.label}
                    </Link>
                ))}
                {trailing}
            </nav>
        </header>
    );
}
