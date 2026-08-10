import AuthLayout from '@/components/auth-layout';
import Button, { buttonClassName } from '@/components/ui/button';
import { StatusState } from '@/components/ui/status-state';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslations } from '@/hooks/use-translations';

export default function VerifyEmail() {
    const { status } = usePage<{ status?: string }>().props;
    const resend = useForm({});
    const { translations } = useTranslations();
    const text = translations.auth.verify as Record<string, string>;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        resend.post('/email/verification-notification');
    }

    return (
        <AuthLayout title={text.title} description={text.description}>
            <Head title={text.page_title} />
            {status === 'verification-link-sent' && <StatusState compact tone="success" title={text.sent} className="mb-5" />}
            <form onSubmit={submit} className="flex flex-col gap-4">
                <Button type="submit" loading={resend.processing}>{resend.processing ? text.resending : text.resend}</Button>
                <Link href="/logout" method="post" as="button" className={buttonClassName('ghost')}>{text.logout}</Link>
            </form>
        </AuthLayout>
    );
}
