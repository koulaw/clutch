import { usePage } from '@inertiajs/react';

export type TranslationCatalog = Record<string, Record<string, string | Record<string, string>>>;

type SharedProps = {
    locale: 'fr' | 'en';
    translations: TranslationCatalog;
};

export function useTranslations() {
    return usePage<SharedProps>().props;
}

export function interpolate(message: string, replacements: Record<string, string>): string {
    return Object.entries(replacements).reduce(
        (translated, [key, value]) => translated.replace(`:${key}`, value),
        message,
    );
}
