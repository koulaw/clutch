import { Link } from '@inertiajs/react';
import type { PropsWithChildren, ReactNode } from 'react';
import LanguageSwitcher from '@/components/language-switcher';
import Card from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';

type AuthLayoutProps = PropsWithChildren<{
    title: string;
    description: string;
    footer?: ReactNode;
}>;

export default function AuthLayout({ children, title, description, footer }: AuthLayoutProps) {
    const { translations } = useTranslations();
    const common = translations.common as Record<string, string>;

    return (
        <main className="relative grid min-h-screen place-items-center overflow-hidden bg-background px-6 py-12 text-text-primary">
            <div className="pointer-events-none absolute -top-40 right-[-12rem] size-[30rem] rounded-full bg-ct/10 blur-3xl" />
            <div className="pointer-events-none absolute -bottom-48 left-[-10rem] size-[30rem] rounded-full bg-accent/10 blur-3xl" />

            <section className="relative z-10 w-full max-w-md">
                <div className="mb-10 flex items-center justify-between gap-6">
                    <Link href="/" className="inline-flex" aria-label={common.home_label}>
                        <img src="/images/brand/logo-dark.svg" alt="Clutch." className="h-11 w-auto" />
                    </Link>
                    <LanguageSwitcher />
                </div>

                <Card elevated className="bg-surface/95 p-6 backdrop-blur sm:p-8">
                    <div className="flex flex-col gap-2">
                        <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
                        <p className="text-sm leading-6 text-text-secondary">{description}</p>
                    </div>

                    <div className="mt-8">{children}</div>

                    {footer && <div className="mt-6 border-t border-border pt-6 text-center text-sm text-text-secondary">{footer}</div>}
                </Card>
            </section>
        </main>
    );
}
