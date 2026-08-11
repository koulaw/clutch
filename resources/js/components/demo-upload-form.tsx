import { useRef, useState } from 'react';
import Button from '@/components/ui/button';
import Card from '@/components/ui/card';
import { interpolate } from '@/hooks/use-translations';

const MAX_FILE_SIZE = 500 * 1024 * 1024;

type UploadPhase = 'idle' | 'hashing' | 'uploading' | 'confirming' | 'success' | 'error';

type DemoUploadFormProps = {
    importsRemaining: number;
    analysesRemaining: number;
    text: Record<string, string>;
    onUploaded: () => void;
};

type Reservation = {
    demo_id: string;
    upload_url: string;
    upload_headers: Record<string, string | number>;
};

type ApiError = {
    code?: string;
    message?: string;
    errors?: Record<string, string[]>;
};

export default function DemoUploadForm({ analysesRemaining, importsRemaining, onUploaded, text }: DemoUploadFormProps) {
    const [file, setFile] = useState<File | null>(null);
    const [phase, setPhase] = useState<UploadPhase>('idle');
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState('');
    const [isDragging, setIsDragging] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);
    const requestRef = useRef<XMLHttpRequest | null>(null);
    const isUnavailable = importsRemaining <= 0 || analysesRemaining <= 0;
    const isProcessing = phase === 'hashing' || phase === 'uploading' || phase === 'confirming';

    function selectFile(selectedFile: File | null) {
        setError('');
        setPhase('idle');
        setProgress(0);

        if (!selectedFile) {
            setFile(null);
            return;
        }

        const validationError = validateFile(selectedFile, text);

        if (validationError) {
            setFile(null);
            setError(validationError);
            setPhase('error');
            if (inputRef.current) {
                inputRef.current.value = '';
            }
            return;
        }

        setFile(selectedFile);
    }

    async function submit() {
        if (!file || isProcessing || isUnavailable) {
            return;
        }

        setError('');

        try {
            setPhase('hashing');
            const checksum = await calculateSha256(file);
            const reservation = await reserveUpload(file, checksum);

            setPhase('uploading');
            await uploadFile(reservation, file, requestRef, setProgress);

            setPhase('confirming');
            await confirmUpload(reservation.demo_id);

            setPhase('success');
            setProgress(100);
            setFile(null);
            if (inputRef.current) {
                inputRef.current.value = '';
            }
            onUploaded();
        } catch (caughtError) {
            if (caughtError instanceof DOMException && caughtError.name === 'AbortError') {
                setPhase('idle');
                setProgress(0);
                return;
            }

            setError(caughtError instanceof Error ? translateError(caughtError.message, text) : text.error_generic);
            setPhase('error');
        } finally {
            requestRef.current = null;
        }
    }

    function cancel() {
        requestRef.current?.abort();
        setPhase('idle');
        setProgress(0);
    }

    return (
        <Card className="flex max-w-2xl flex-col gap-5" elevated>
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p className="text-xs font-semibold tracking-[0.16em] text-accent uppercase">{text.eyebrow}</p>
                    <h2 className="mt-2 text-2xl font-semibold tracking-tight">{text.title}</h2>
                    <p className="mt-2 max-w-xl text-sm leading-6 text-text-secondary">{text.description}</p>
                </div>
                <span className="shrink-0 rounded-full border border-border bg-surface px-3 py-1.5 text-xs text-text-secondary">
                    {interpolate(text.quota_summary, { imports: importsRemaining.toString(), analyses: analysesRemaining.toString() })}
                </span>
            </div>

            <div
                role="button"
                tabIndex={isProcessing || isUnavailable ? -1 : 0}
                aria-disabled={isProcessing || isUnavailable}
                className={`relative flex min-h-52 flex-col items-center justify-center gap-3 rounded-xl border border-dashed px-6 py-8 text-center transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent ${
                    isDragging ? 'border-accent bg-accent/10' : 'border-border bg-background/50 hover:border-text-secondary'
                } ${isProcessing || isUnavailable ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'}`}
                onClick={() => !isProcessing && !isUnavailable && inputRef.current?.click()}
                onKeyDown={(event) => {
                    if ((event.key === 'Enter' || event.key === ' ') && !isProcessing && !isUnavailable) {
                        event.preventDefault();
                        inputRef.current?.click();
                    }
                }}
                onDragEnter={(event) => {
                    event.preventDefault();
                    if (!isProcessing && !isUnavailable) setIsDragging(true);
                }}
                onDragOver={(event) => event.preventDefault()}
                onDragLeave={(event) => {
                    event.preventDefault();
                    setIsDragging(false);
                }}
                onDrop={(event) => {
                    event.preventDefault();
                    setIsDragging(false);
                    if (!isProcessing && !isUnavailable) selectFile(event.dataTransfer.files.item(0));
                }}
            >
                <input
                    ref={inputRef}
                    type="file"
                    accept=".dem,.zst,.dem.zst"
                    className="sr-only"
                    disabled={isProcessing || isUnavailable}
                    onChange={(event) => selectFile(event.target.files?.item(0) ?? null)}
                    aria-describedby="demo-upload-help"
                />
                <span className="grid size-12 place-items-center rounded-xl border border-border bg-surface-elevated text-2xl text-accent" aria-hidden="true">
                    ↑
                </span>
                <div>
                    <p className="font-semibold">{file ? file.name : text.drop_title}</p>
                    <p id="demo-upload-help" className="mt-1 text-sm text-text-secondary">
                        {file ? formatFileSize(file.size) : text.drop_description}
                    </p>
                </div>
                {!file && !isUnavailable && <span className="text-sm font-semibold text-accent">{text.browse}</span>}
            </div>

            {isUnavailable && (
                <p className="rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-light" role="alert">
                    {importsRemaining <= 0 ? text.error_import_quota : text.error_analysis_quota}
                </p>
            )}

            {error && (
                <p className="rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-light" role="alert">
                    {error}
                </p>
            )}

            {phase === 'success' && (
                <p className="rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-sm text-success-light" role="status">
                    {text.success}
                </p>
            )}

            {isProcessing && (
                <div className="flex flex-col gap-2" aria-live="polite">
                    <div className="flex items-center justify-between gap-4 text-sm">
                        <span>{phaseLabel(phase, text)}</span>
                        <span className="tabular-nums text-text-secondary">{phase === 'uploading' ? `${progress}%` : ''}</span>
                    </div>
                    <div className="h-2 overflow-hidden rounded-full bg-surface">
                        <div
                            className={`h-full rounded-full bg-accent transition-[width] ${phase === 'hashing' || phase === 'confirming' ? 'w-1/3 animate-pulse' : ''}`}
                            style={phase === 'uploading' ? { width: `${progress}%` } : undefined}
                        />
                    </div>
                </div>
            )}

            <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                {isProcessing && phase === 'uploading' && (
                    <Button variant="ghost" onClick={cancel}>{text.cancel}</Button>
                )}
                <Button loading={isProcessing} disabled={!file || isUnavailable} onClick={submit} className="w-full sm:w-auto">
                    {isProcessing ? phaseLabel(phase, text) : phase === 'error' ? text.retry : text.submit}
                </Button>
            </div>
        </Card>
    );
}

function validateFile(file: File, text: Record<string, string>): string | null {
    const filename = file.name.toLowerCase();
    if (!filename.endsWith('.dem') && !filename.endsWith('.zst')) return text.error_extension;
    if (file.size === 0) return text.error_empty;
    if (file.size > MAX_FILE_SIZE) return text.error_size;
    return null;
}

async function calculateSha256(file: File): Promise<string> {
    const digest = await crypto.subtle.digest('SHA-256', await file.arrayBuffer());
    return Array.from(new Uint8Array(digest), (byte) => byte.toString(16).padStart(2, '0')).join('');
}

async function reserveUpload(file: File, checksum: string): Promise<Reservation> {
    const response = await fetch('/api/v1/demos/upload-url', {
        method: 'POST',
        credentials: 'same-origin',
        headers: jsonHeaders(),
        body: JSON.stringify({ filename: file.name, size_bytes: file.size, checksum_sha256: checksum }),
    });
    const payload = await parseResponse<{ data: Reservation }>(response);
    return payload.data;
}

function uploadFile(reservation: Reservation, file: File, requestRef: React.MutableRefObject<XMLHttpRequest | null>, setProgress: (progress: number) => void): Promise<void> {
    return new Promise((resolve, reject) => {
        const request = new XMLHttpRequest();
        requestRef.current = request;
        request.open('PUT', reservation.upload_url);

        Object.entries(reservation.upload_headers).forEach(([name, value]) => {
            if (name.toLowerCase() !== 'content-length') request.setRequestHeader(name, String(value));
        });

        request.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) setProgress(Math.round((event.loaded / event.total) * 100));
        });
        request.addEventListener('load', () => request.status >= 200 && request.status < 300 ? resolve() : reject(new Error('storage')));
        request.addEventListener('error', () => reject(new Error('network')));
        request.addEventListener('abort', () => reject(new DOMException('Upload cancelled', 'AbortError')));
        request.send(file);
    });
}

async function confirmUpload(demoId: string): Promise<void> {
    const response = await fetch(`/api/v1/demos/${encodeURIComponent(demoId)}/confirm`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: jsonHeaders(),
    });
    await parseResponse(response);
}

function jsonHeaders(): Record<string, string> {
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
    };
}

async function parseResponse<T = unknown>(response: Response): Promise<T> {
    const payload = await response.json().catch(() => ({})) as T & ApiError;
    if (response.ok) return payload;

    const validationMessage = payload.errors ? Object.values(payload.errors).flat()[0] : undefined;
    throw new Error(payload.code ?? validationMessage ?? payload.message ?? `http_${response.status}`);
}

function translateError(code: string, text: Record<string, string>): string {
    if (code === 'quota_exceeded') return text.error_import_quota;
    if (code === 'demo_already_uploaded') return text.error_duplicate;
    if (code === 'upload_rate_limit_exceeded' || code === 'http_429') return text.error_rate_limit;
    if (code === 'storage') return text.error_storage;
    if (code === 'network' || code === 'Failed to fetch') return text.error_network;
    return text.error_generic;
}

function phaseLabel(phase: UploadPhase, text: Record<string, string>): string {
    if (phase === 'hashing') return text.hashing;
    if (phase === 'uploading') return text.uploading;
    return text.confirming;
}

function formatFileSize(bytes: number): string {
    return `${(bytes / 1024 / 1024).toLocaleString(undefined, { maximumFractionDigits: 1 })} MB`;
}
