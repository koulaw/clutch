import AuthLayout from '@/components/auth-layout';
import FormField from '@/components/form-field';
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
            {status && <p className="mb-5 rounded-lg border border-ct-dark bg-ct-dark/20 p-3 text-sm text-ct">{status}</p>}
            <form onSubmit={submit} className="flex flex-col gap-5">
                <FormField id="email" label={text.email} type="email" autoComplete="email" autoFocus required value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} />
                <button type="submit" disabled={form.processing} className="rounded-lg bg-accent px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#e05a17] disabled:opacity-60">{form.processing ? text.submitting : text.submit}</button>
            </form>
        </AuthLayout>
    );
}
