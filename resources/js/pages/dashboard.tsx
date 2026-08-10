import { Head, usePage } from '@inertiajs/react';
import LanguageSwitcher from '@/components/language-switcher';
import Badge from '@/components/ui/badge';
import Navigation from '@/components/ui/navigation';
import { StatusState } from '@/components/ui/status-state';
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
                <Navigation
                    homeLabel={common.home_label}
                    actions={[{ href: '/logout', label: text.logout, method: 'post', variant: 'secondary' }]}
                    trailing={<LanguageSwitcher />}
                />
                <section className="flex flex-col gap-8 py-16">
                    <div>
                        <Badge tone="success">{text.verified}</Badge>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight">{interpolate(text.welcome, { name: auth.user.name })}</h1>
                        <p className="mt-4 max-w-xl leading-7 text-text-secondary">{text.description}</p>
                    </div>
                    <StatusState title={text.empty_title} description={text.empty_description} className="max-w-2xl" />
                </section>
            </div>
            <Head title={text.page_title} />
        </main>
    );
}
