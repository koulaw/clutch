import AuthLayout from '@/components/auth-layout';
import FormField from '@/components/form-field';
import Button from '@/components/ui/button';
import { StatusState } from '@/components/ui/status-state';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslations } from '@/hooks/use-translations';

export default function ForgotPassword() {
    const { status } = usePage<{ status?: string }>().props;
    const form = useForm({ email: '' });
    const { translations } = useTranslations();
    const text = translations.auth.forgot as Record<string, string>;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post('/forgot-password');
    }

    return (
        <AuthLayout title={text.title} description={text.description} footer={<Link href="/login" className="font-medium text-ct hover:underline">{text.back}</Link>}>
            <Head title={text.page_title} />
            {status && <StatusState compact tone="success" title={status} className="mb-5" />}
            <form onSubmit={submit} className="flex flex-col gap-5">
                <FormField id="email" label={text.email} type="email" autoComplete="email" autoFocus required value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} />
                <Button type="submit" loading={form.processing}>{form.processing ? text.submitting : text.submit}</Button>
            </form>
        </AuthLayout>
    );
}
