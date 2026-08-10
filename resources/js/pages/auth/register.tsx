import AuthLayout from '@/components/auth-layout';
import FormField from '@/components/form-field';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslations } from '@/hooks/use-translations';

export default function Register() {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
    const { translations } = useTranslations();
    const text = translations.auth.register as Record<string, string>;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
    }

    return (
        <AuthLayout title={text.title} description={text.description} footer={<>{text.has_account} <Link href="/login" className="font-medium text-ct hover:underline">{text.login}</Link></>}>
            <Head title={text.page_title} />
            <form onSubmit={submit} className="flex flex-col gap-5">
                <FormField id="name" label={text.name} autoComplete="name" autoFocus required value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} error={form.errors.name} />
                <FormField id="email" label={text.email} type="email" autoComplete="email" required value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} />
                <FormField id="password" label={text.password} type="password" autoComplete="new-password" required value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={form.errors.password} />
                <FormField id="password_confirmation" label={text.password_confirmation} type="password" autoComplete="new-password" required value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} />
                <button type="submit" disabled={form.processing} className="rounded-lg bg-accent px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#e05a17] disabled:cursor-not-allowed disabled:opacity-60">{form.processing ? text.submitting : text.submit}</button>
            </form>
        </AuthLayout>
    );
}
