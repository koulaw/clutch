import { router } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';

export default function LanguageSwitcher() {
    const { locale, translations } = useTranslations();
    const common = translations.common as Record<string, string>;

    function updateLocale(nextLocale: 'fr' | 'en') {
        router.post('/locale', { locale: nextLocale }, {
            preserveScroll: true,
            onSuccess: () => document.documentElement.setAttribute('lang', nextLocale),
        });
    }

    return (
        <div className="flex items-center rounded-lg border border-border bg-surface p-1" aria-label={common.language}>
            {(['fr', 'en'] as const).map((option) => (
                <button
                    key={option}
                    type="button"
                    onClick={() => updateLocale(option)}
                    className={`rounded-md px-2 py-1 text-xs font-medium uppercase transition ${locale === option ? 'bg-surface-elevated text-text-primary' : 'text-text-secondary hover:text-text-primary'}`}
                    aria-pressed={locale === option}
                >
                    {option}
                </button>
            ))}
        </div>
    );
}
