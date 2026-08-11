import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AnalysisProgressList from '@/components/analysis-progress-list';
import DemoUploadForm from '@/components/demo-upload-form';
import LanguageSwitcher from '@/components/language-switcher';
import Badge from '@/components/ui/badge';
import Card from '@/components/ui/card';
import Navigation from '@/components/ui/navigation';
import { interpolate, useTranslations } from '@/hooks/use-translations';

type DashboardProps = {
    auth: { user: { name: string; email: string } };
    quotas: {
        imports: { used: number; limit: number };
        analyses: { used: number; limit: number };
    };
};

export default function Dashboard() {
    const { auth, quotas } = usePage<DashboardProps>().props;
    const { translations } = useTranslations();
    const text = translations.dashboard as Record<string, string>;
    const common = translations.common as Record<string, string>;
    const [analysisRefreshToken, setAnalysisRefreshToken] = useState(0);

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
                    <DemoUploadForm
                        importsRemaining={Math.max(0, quotas.imports.limit - quotas.imports.used)}
                        analysesRemaining={Math.max(0, quotas.analyses.limit - quotas.analyses.used)}
                        text={translations.demo_upload as Record<string, string>}
                        onUploaded={() => {
                            router.reload({ only: ['quotas'] });
                            setAnalysisRefreshToken((token) => token + 1);
                        }}
                    />
                    <div className="grid max-w-2xl gap-4 sm:grid-cols-2" aria-label={text.quota_title}>
                        <QuotaCard
                            label={text.import_quota}
                            used={quotas.imports.used}
                            limit={quotas.imports.limit}
                            remainingLabel={text.remaining_today}
                        />
                        <QuotaCard
                            label={text.analysis_quota}
                            used={quotas.analyses.used}
                            limit={quotas.analyses.limit}
                            remainingLabel={text.remaining_total}
                        />
                    </div>
                    <AnalysisProgressList refreshToken={analysisRefreshToken} text={translations.analysis_progress as Record<string, string>} />
                </section>
            </div>
            <Head title={text.page_title} />
        </main>
    );
}

type QuotaCardProps = {
    label: string;
    used: number;
    limit: number;
    remainingLabel: string;
};

function QuotaCard({ label, used, limit, remainingLabel }: QuotaCardProps) {
    return (
        <Card className="flex flex-col gap-3">
            <p className="text-sm font-medium text-text-secondary">{label}</p>
            <p className="text-3xl font-semibold tracking-tight">
                {used} <span className="text-base font-normal text-text-secondary">/ {limit}</span>
            </p>
            <p className="text-sm text-text-secondary">
                {interpolate(remainingLabel, { count: Math.max(0, limit - used).toString() })}
            </p>
        </Card>
    );
}
