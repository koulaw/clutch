import { Head, Link, usePage } from '@inertiajs/react';
import LanguageSwitcher from '@/components/language-switcher';
import { interpolate, useTranslations } from '@/hooks/use-translations';

type DashboardProps = {
    auth: { user: { name: string; email: string } };
};

export default function Dashboard() {
    const { auth } = usePage<DashboardProps>().props;
    const { translations } = useTranslations();
    const text = translations.dashboard as Record<string, string>;
    const common = translations.common as Record<string, string>;

    return (
        <main className="min-h-screen bg-background px-6 py-8 text-text-primary">
            <div className="mx-auto max-w-6xl">
                <header className="flex items-center justify-between gap-6 border-b border-border pb-6">
                    <Link href="/" aria-label={common.home_label}><img src="/images/brand/logo-dark.svg" alt="Clutch." className="h-10 w-auto" /></Link>
                    <div className="flex items-center gap-3">
                        <Link href="/logout" method="post" as="button" className="rounded-lg border border-border bg-surface px-4 py-2 text-sm text-text-secondary transition hover:text-text-primary">{text.logout}</Link>
                        <LanguageSwitcher />
                    </div>
                </header>
                <section className="py-16">
                    <p className="text-sm font-medium text-ct">{text.verified}</p>
                    <h1 className="mt-3 text-4xl font-semibold tracking-tight">{interpolate(text.welcome, { name: auth.user.name })}</h1>
                    <p className="mt-4 max-w-xl leading-7 text-text-secondary">{text.description}</p>
                </section>
            </div>
            <Head title={text.page_title} />
        </main>
    );
}
