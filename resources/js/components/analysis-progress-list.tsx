import { useCallback, useEffect, useState } from 'react';
import Badge from '@/components/ui/badge';
import Button from '@/components/ui/button';
import Card from '@/components/ui/card';
import { LoadingState, StatusState } from '@/components/ui/status-state';
import { interpolate } from '@/hooks/use-translations';

type AnalysisError = {
    code: string;
    message: string;
    retryable: boolean;
};

type AnalysisProgress = {
    id: number;
    demo_id: string;
    attempt: number;
    status: 'queued' | 'parsing' | 'analyzing' | 'ready' | 'failed' | 'unsupported';
    progress: number;
    is_terminal: boolean;
    can_retry: boolean;
    error: AnalysisError | null;
    updated_at: string;
};

type AnalysisProgressListProps = {
    refreshToken: number;
    text: Record<string, string>;
};

export default function AnalysisProgressList({ refreshToken, text }: AnalysisProgressListProps) {
    const [analyses, setAnalyses] = useState<AnalysisProgress[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [retryingId, setRetryingId] = useState<number | null>(null);
    const [requestError, setRequestError] = useState('');

    const refresh = useCallback(async () => {
        try {
            const response = await fetch('/api/v1/analyses', {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) throw new Error(`http_${response.status}`);
            const payload = await response.json() as { data: AnalysisProgress[] };
            setAnalyses(payload.data);
            setRequestError('');
        } catch {
            setRequestError(text.refresh_error);
        } finally {
            setIsLoading(false);
        }
    }, [text.refresh_error]);

    useEffect(() => {
        void refresh();
        const interval = window.setInterval(() => void refresh(), 3000);
        return () => window.clearInterval(interval);
    }, [refresh, refreshToken]);

    async function retry(analysis: AnalysisProgress) {
        setRetryingId(analysis.id);
        setRequestError('');

        try {
            const response = await fetch(`/api/v1/demos/${encodeURIComponent(analysis.demo_id)}/analysis/retry`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(),
            });

            if (!response.ok) throw new Error(`http_${response.status}`);
            await refresh();
        } catch {
            setRequestError(text.retry_error);
        } finally {
            setRetryingId(null);
        }
    }

    if (isLoading) return <LoadingState label={text.loading} />;

    if (analyses.length === 0) {
        return <StatusState title={text.empty_title} description={text.empty_description} />;
    }

    return (
        <section className="flex max-w-2xl flex-col gap-4" aria-labelledby="analysis-progress-title" aria-live="polite">
            <div className="flex items-center justify-between gap-4">
                <h2 id="analysis-progress-title" className="text-xl font-semibold tracking-tight">{text.title}</h2>
                <span className="text-xs text-text-secondary">{text.polling}</span>
            </div>
            {requestError && <p className="rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-light" role="alert">{requestError}</p>}
            {analyses.map((analysis) => (
                <Card key={analysis.id} className="flex flex-col gap-4">
                    <div className="flex items-start justify-between gap-4">
                        <div className="flex flex-col gap-1">
                            <p className="font-semibold">{interpolate(text.analysis_label, { id: analysis.demo_id.slice(-6) })}</p>
                            <p className="text-xs text-text-secondary">{interpolate(text.attempt, { attempt: analysis.attempt.toString() })}</p>
                        </div>
                        <Badge tone={statusTone(analysis.status)}>{text[`status_${analysis.status}`]}</Badge>
                    </div>
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center justify-between gap-4 text-sm">
                            <span>{text[`status_description_${analysis.status}`]}</span>
                            <span className="tabular-nums text-text-secondary">{analysis.progress}%</span>
                        </div>
                        <div className="h-2 overflow-hidden rounded-full bg-surface-raised" role="progressbar" aria-valuemin={0} aria-valuemax={100} aria-valuenow={analysis.progress}>
                            <div className={`h-full rounded-full transition-[width] ${progressTone(analysis.status)}`} style={{ width: `${analysis.progress}%` }} />
                        </div>
                    </div>
                    {analysis.error && (
                        <div className="flex flex-col gap-3 rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-light" role="alert">
                            <p>{analysis.status === 'unsupported' ? text.error_unsupported : text.error_failed}</p>
                            {analysis.can_retry && <Button variant="secondary" size="sm" loading={retryingId === analysis.id} onClick={() => void retry(analysis)} className="self-start">{text.retry}</Button>}
                        </div>
                    )}
                </Card>
            ))}
        </section>
    );
}

function jsonHeaders(): Record<string, string> {
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
    };
}

function statusTone(status: AnalysisProgress['status']): 'neutral' | 'success' | 'danger' {
    if (status === 'ready') return 'success';
    if (status === 'failed' || status === 'unsupported') return 'danger';
    return 'neutral';
}

function progressTone(status: AnalysisProgress['status']): string {
    if (status === 'ready') return 'bg-success';
    if (status === 'failed' || status === 'unsupported') return 'bg-danger';
    return 'bg-accent';
}
