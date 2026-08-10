import AuthLayout from '@/components/auth-layout';
import FormField from '@/components/form-field';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslations } from '@/hooks/use-translations';

export default function Login() {
    const { status } = usePage<{ status?: string }>().props;
    const form = useForm({ email: '', password: '', remember: false });
    const { translations } = useTranslations();
    const text = translations.auth.login as Record<string, string>;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    }

    return (
        <AuthLayout title={text.title} description={text.description} footer={<>{text.no_account} <Link href="/register" className="font-medium text-ct hover:underline">{text.create_account}</Link></>}>
            <Head title={text.page_title} />
            {status && <p className="mb-5 rounded-lg border border-ct-dark bg-ct-dark/20 p-3 text-sm text-ct">{status}</p>}
            <form onSubmit={submit} className="flex flex-col gap-5">
                <FormField id="email" label={text.email} type="email" autoComplete="email" autoFocus required value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} />
                <FormField id="password" label={text.password} type="password" autoComplete="current-password" required value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={form.errors.password} />
                <div className="flex items-center justify-between gap-4 text-sm">
                    <label className="flex items-center gap-2 text-text-secondary"><input type="checkbox" checked={form.data.remember} onChange={(event) => form.setData('remember', event.target.checked)} className="size-4 accent-ct" />{text.remember}</label>
                    <Link href="/forgot-password" className="text-ct hover:underline">{text.forgot}</Link>
                </div>
                <button type="submit" disabled={form.processing} className="rounded-lg bg-accent px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#e05a17] disabled:cursor-not-allowed disabled:opacity-60">{form.processing ? text.submitting : text.submit}</button>
            </form>
        </AuthLayout>
    );
}
