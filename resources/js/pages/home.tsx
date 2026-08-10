import { Head } from '@inertiajs/react';

export default function Home() {
    return (
        <>
            <Head title="Accueil" />

            <main className="grid min-h-screen place-items-center bg-zinc-950 px-6 text-zinc-100">
                <section className="flex max-w-2xl flex-col gap-6 text-center">
                    <p className="text-sm font-medium tracking-[0.3em] text-orange-400 uppercase">
                        Counter-Strike 2 demo analysis
                    </p>
                    <h1 className="text-6xl font-semibold tracking-tight sm:text-8xl">
                        Clutch.
                    </h1>
                    <p className="text-base leading-7 text-zinc-400 sm:text-lg">
                        Le socle Inertia, React et TypeScript est prêt pour construire le lecteur radar 2D.
                    </p>
                </section>
            </main>
        </>
    );
}
