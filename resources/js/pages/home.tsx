import { Head } from '@inertiajs/react';

export default function Home() {
    return (
        <>
            <Head title="Accueil" />

            <div className="relative min-h-screen overflow-hidden bg-background text-text-primary">
                <div
                    className="pointer-events-none absolute -top-40 right-[-12rem] size-[32rem] rounded-full bg-ct/10 blur-3xl"
                    aria-hidden="true"
                />
                <div
                    className="pointer-events-none absolute bottom-[-16rem] left-[-12rem] size-[34rem] rounded-full bg-accent/10 blur-3xl"
                    aria-hidden="true"
                />

                <header className="relative z-10 mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-6 lg:px-10">
                    <a href="/" aria-label="Clutch — Accueil">
                        <img
                            src="/images/brand/logo-dark.svg"
                            alt="Clutch."
                            className="h-10 w-auto sm:h-12"
                        />
                    </a>

                    <span className="rounded-full border border-border bg-surface/80 px-3 py-1.5 text-xs font-medium tracking-wide text-text-secondary uppercase backdrop-blur">
                        Bêta privée
                    </span>
                </header>

                <main className="relative z-10 mx-auto grid w-full max-w-7xl items-center gap-16 px-6 py-12 lg:min-h-[calc(100vh-168px)] lg:grid-cols-[0.88fr_1.12fr] lg:px-10 lg:py-16">
                    <section className="flex max-w-2xl flex-col items-start gap-8">
                        <div className="flex items-center gap-3 text-sm font-medium text-text-secondary">
                            <span className="size-2 rounded-full bg-accent shadow-[0_0_18px_rgba(207,80,21,0.9)]" />
                            Analyse de démos Counter-Strike 2
                        </div>

                        <div className="flex flex-col gap-5">
                            <h1 className="text-5xl leading-[0.98] font-semibold tracking-[-0.045em] text-balance sm:text-6xl lg:text-7xl">
                                Revoyez le round.
                                <span className="block text-text-secondary">Comprenez la décision.</span>
                            </h1>
                            <p className="max-w-xl text-base leading-7 text-text-secondary sm:text-lg sm:leading-8">
                                Un lecteur radar 2D, des statistiques précises et des conseils reliés aux moments qui ont vraiment changé votre partie.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <span className="rounded-full border border-ct-dark bg-ct-dark/30 px-4 py-2 text-sm text-ct">
                                Radar 2D
                            </span>
                            <span className="rounded-full border border-t-dark bg-t-dark/20 px-4 py-2 text-sm text-t">
                                Statistiques par round
                            </span>
                            <span className="rounded-full border border-border bg-surface-elevated px-4 py-2 text-sm text-text-secondary">
                                Coaching explicable
                            </span>
                        </div>

                        <a
                            href="#preview"
                            className="inline-flex items-center gap-3 rounded-lg bg-accent px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#e05a17] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
                        >
                            Découvrir Clutch.
                            <span aria-hidden="true">→</span>
                        </a>
                    </section>

                    <section
                        id="preview"
                        className="relative rounded-2xl border border-border bg-surface/90 p-3 shadow-2xl shadow-black/30 backdrop-blur sm:p-4"
                    >
                        <div className="flex items-center justify-between border-b border-border px-2 pb-3">
                            <div className="flex items-center gap-3">
                                <span className="grid size-8 place-items-center rounded-md bg-surface-elevated">
                                    <img
                                        src="/images/brand/icon-dark.svg"
                                        alt=""
                                        className="size-6"
                                        aria-hidden="true"
                                    />
                                </span>
                                <div>
                                    <p className="text-sm font-medium">Match analysis</p>
                                    <p className="text-xs text-text-secondary">de_mirage · Round 18</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2 text-xs font-semibold">
                                <span className="text-ct">9</span>
                                <span className="text-text-secondary">:</span>
                                <span className="text-t">8</span>
                            </div>
                        </div>

                        <div className="grid gap-3 pt-3 sm:grid-cols-[1fr_12rem]">
                            <div className="relative aspect-square overflow-hidden rounded-xl border border-border bg-map-dark">
                                <div className="absolute inset-[12%] rotate-[-7deg] rounded-[35%_16%_28%_22%] border-2 border-map-light/70" />
                                <div className="absolute top-[23%] right-[18%] h-[42%] w-[28%] rotate-12 border-t-2 border-r-2 border-map-light/50" />
                                <div className="absolute bottom-[16%] left-[18%] h-[30%] w-[36%] -rotate-6 border-b-2 border-l-2 border-map-light/50" />
                                <span className="absolute top-[24%] left-[30%] size-3 rounded-full border-2 border-background bg-ct shadow-[0_0_12px_rgba(44,140,233,0.75)]" />
                                <span className="absolute top-[36%] left-[40%] size-3 rounded-full border-2 border-background bg-ct" />
                                <span className="absolute right-[31%] bottom-[30%] size-3 rounded-full border-2 border-background bg-t shadow-[0_0_12px_rgba(240,182,29,0.65)]" />
                                <span className="absolute right-[22%] bottom-[40%] size-3 rounded-full border-2 border-background bg-t" />
                                <span className="absolute bottom-[18%] left-[46%] size-2.5 rounded-sm bg-accent shadow-[0_0_14px_rgba(207,80,21,0.8)]" />
                                <div className="absolute right-3 bottom-3 rounded bg-background/80 px-2 py-1 text-[10px] text-text-secondary">
                                    01:14
                                </div>
                            </div>

                            <div className="flex flex-col gap-3">
                                <article className="rounded-xl border border-border bg-surface-elevated p-4">
                                    <p className="text-xs text-text-secondary">Impact du round</p>
                                    <p className="mt-2 text-2xl font-semibold">+24%</p>
                                    <p className="mt-1 text-xs text-ct">Trade réussi</p>
                                </article>
                                <article className="flex-1 rounded-xl border border-border bg-surface-elevated p-4">
                                    <p className="text-xs text-text-secondary">À revoir</p>
                                    <p className="mt-2 text-sm leading-6">
                                        Votre rotation commence 2,4 s après le premier contact.
                                    </p>
                                    <span className="mt-3 inline-block text-xs font-medium text-accent">
                                        Voir à 00:42 →
                                    </span>
                                </article>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="relative z-10 mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-6 text-xs text-text-secondary lg:px-10">
                    <p>Clutch. — Play. Review. Improve.</p>
                    <p>Laravel · Inertia · React</p>
                </footer>
            </div>
        </>
    );
}
