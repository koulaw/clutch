import AuthLayout from '@/components/auth-layout';
import FormField from '@/components/form-field';
import Button from '@/components/ui/button';
import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslations } from '@/hooks/use-translations';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const form = useForm({ token, email, password: '', password_confirmation: '' });
    const { translations } = useTranslations();
    const text = translations.auth.reset as Record<string, string>;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
    }

    return (
        <AuthLayout title={text.title} description={text.description}>
            <Head title={text.page_title} />
            <form onSubmit={submit} className="flex flex-col gap-5">
                <FormField id="email" label={text.email} type="email" autoComplete="email" required value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} />
                <FormField id="password" label={text.password} type="password" autoComplete="new-password" autoFocus required value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={form.errors.password} />
                <FormField id="password_confirmation" label={text.password_confirmation} type="password" autoComplete="new-password" required value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} />
                <Button type="submit" loading={form.processing}>{form.processing ? text.submitting : text.submit}</Button>
            </form>
        </AuthLayout>
    );
}
