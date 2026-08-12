import { Head, Link, usePage } from '@inertiajs/react';
import { ClipboardList, MailWarning, ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

const flowSteps = [
    'Email simulasi dikirim ke responden',
    'Responden menanggapi portal login tiruan',
    'Perilaku aktual direkam (berisiko / waspada / netral)',
    'Responden mengisi kuesioner KAB',
    'Data gabungan diekspor untuk klasifikasi',
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Sistem Simulasi Kesadaran Keamanan Phishing" />

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                {/* Header */}
                <header className="border-b border-border">
                    <div className="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-2.5">
                            <div className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                <ShieldCheck className="size-5" />
                            </div>
                            <span className="text-sm font-semibold tracking-tight">
                                Simulasi Kesadaran Phishing
                            </span>
                        </div>

                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild size="sm">
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href={login()}>Masuk</Link>
                                    </Button>
                                    <Button asChild size="sm">
                                        <Link href={register()}>Daftar</Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                {/* Hero */}
                <main className="mx-auto grid w-full max-w-5xl flex-1 items-center gap-12 px-6 py-14 lg:grid-cols-2 lg:py-20">
                    <div>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Instrumen Penelitian Skripsi
                        </p>
                        <h1 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                            Mengukur kesadaran keamanan informasi siswa terhadap
                            ancaman phishing
                        </h1>
                        <p className="mt-4 max-w-prose leading-relaxed text-muted-foreground">
                            Aplikasi ini adalah alat pengumpulan data untuk
                            penelitian di SMA Negeri 1 Kendari. Menggabungkan
                            simulasi phishing tersamar (perilaku aktual) dengan
                            kuesioner Knowledge–Attitude–Behavior (KAB), lalu
                            menyiapkan dataset untuk klasifikasi menggunakan
                            algoritma Random Forest.
                        </p>

                        <div className="mt-7 flex flex-wrap gap-3">
                            <Button asChild>
                                <Link href={auth.user ? dashboard() : login()}>
                                    Masuk Panel Peneliti
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <a href="#metode">Pelajari Metode</a>
                            </Button>
                        </div>

                        <p className="mt-6 text-sm text-muted-foreground">
                            Simulasi edukatif dengan izin institusional — bukan
                            serangan nyata. Sistem tidak pernah menyimpan kata
                            sandi atau data yang diketik responden.
                        </p>
                    </div>

                    {/* Flow panel */}
                    <div className="rounded-xl border border-border bg-card p-6 shadow-sm">
                        <p className="text-sm font-medium">
                            Alur Pengumpulan Data
                        </p>
                        <ol className="mt-4 space-y-3">
                            {flowSteps.map((step, i) => (
                                <li key={i} className="flex gap-3">
                                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full border border-border text-xs font-medium text-muted-foreground">
                                        {i + 1}
                                    </span>
                                    <span className="pt-0.5 text-sm text-muted-foreground">
                                        {step}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    </div>
                </main>

                {/* Instruments */}
                <section
                    id="metode"
                    className="border-t border-border bg-muted/30"
                >
                    <div className="mx-auto w-full max-w-5xl px-6 py-14">
                        <h2 className="text-lg font-semibold tracking-tight">
                            Dua Instrumen Terhubung
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Ditautkan lewat token unik per responden sehingga
                            perilaku dan jawaban dapat dipasangkan.
                        </p>

                        <div className="mt-6 grid gap-4 sm:grid-cols-2">
                            <div className="rounded-xl border border-border bg-card p-5">
                                <MailWarning className="size-5 text-muted-foreground" />
                                <h3 className="mt-3 font-medium">
                                    Simulasi Phishing Tersamar
                                </h3>
                                <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                                    Mengukur perilaku aktual: apakah responden
                                    mengklik, mengisi, atau justru melaporkan
                                    email mencurigakan — beserta metadata
                                    perangkat dan waktu respons.
                                </p>
                            </div>
                            <div className="rounded-xl border border-border bg-card p-5">
                                <ClipboardList className="size-5 text-muted-foreground" />
                                <h3 className="mt-3 font-medium">
                                    Kuesioner KAB
                                </h3>
                                <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                                    Mengukur laporan-diri pada tiga dimensi —
                                    Pengetahuan, Sikap, dan Perilaku — sebagai
                                    pelengkap data perilaku aktual dari
                                    simulasi.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-border">
                    <div className="mx-auto w-full max-w-5xl px-6 py-6 text-xs text-muted-foreground">
                        <p>
                            Klasifikasi Tingkat Kesadaran Keamanan Informasi
                            Siswa terhadap Ancaman Phishing menggunakan
                            Pendekatan KAB dan Algoritma Random Forest.
                        </p>
                        <p className="mt-1">
                            SMA Negeri 1 Kendari — Instrumen penelitian
                            akademik.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
