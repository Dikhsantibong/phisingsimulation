import { Head } from '@inertiajs/react';
import {
    BookOpen,
    ShieldAlert,
    BadgeInfo,
    AlertTriangle,
    CheckCircle,
} from 'lucide-react';
import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';

type Props = {
    token: string;
    behavior_status?: string;
    keystroke_detected?: boolean;
    isCompleted?: boolean;
    questionnaireUrl?: string | null;
};

export default function Reveal({ behavior_status, isCompleted, questionnaireUrl }: Props) {
    const [showEdu, setShowEdu] = useState(isCompleted ? true : false);
    const [countdown, setCountdown] = useState<number | null>(null);

    useEffect(() => {
        if (!isCompleted && questionnaireUrl) {
            setCountdown(8); // Wait 8 seconds before redirecting
        }
    }, [isCompleted, questionnaireUrl]);

    useEffect(() => {
        if (countdown === null) return;
        if (countdown <= 0) {
            window.location.href = questionnaireUrl!;
            return;
        }

        const timer = setTimeout(() => {
            setCountdown(countdown - 1);
        }, 1000);

        return () => clearTimeout(timer);
    }, [countdown, questionnaireUrl]);

    let resultBox = null;

    if (behavior_status === 'berisiko') {
        resultBox = (
            <div className="rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-900/50 dark:bg-red-950/20">
                <div className="flex items-center gap-3 text-red-700 dark:text-red-400">
                    <AlertTriangle className="size-6" />
                    <h2 className="text-lg font-bold">Status: Berisiko</h2>
                </div>
                <p className="mt-2 text-sm text-red-600 dark:text-red-300">
                    Anda memasukkan dan mengirimkan data Anda pada situs
                    phishing ini. Di dunia nyata, akun Anda mungkin sudah
                    diretas.
                </p>
            </div>
        );
    } else if (behavior_status === 'waspada') {
        resultBox = (
            <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                <div className="flex items-center gap-3 text-emerald-700 dark:text-emerald-400">
                    <CheckCircle className="size-6" />
                    <h2 className="text-lg font-bold">Status: Waspada</h2>
                </div>
                <p className="mt-2 text-sm text-emerald-600 dark:text-emerald-300">
                    Sangat baik! Anda berhasil mengenali situs ini sebagai
                    halaman mencurigakan dan berhasil menghindarinya.
                </p>
            </div>
        );
    } else {
        resultBox = (
            <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900/50 dark:bg-amber-950/20">
                <div className="flex items-center gap-3 text-amber-700 dark:text-amber-400">
                    <BadgeInfo className="size-6" />
                    <h2 className="text-lg font-bold">Status: Netral</h2>
                </div>
                <p className="mt-2 text-sm text-amber-600 dark:text-amber-300">
                    Anda sempat mengetikkan sesuatu, namun pada akhirnya Anda
                    menolak atau melaporkan halaman ini. Berhati-hatilah agar
                    tidak terburu-buru memasukkan data di masa depan.
                </p>
            </div>
        );
    }

    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 p-4 py-10 dark:bg-slate-950">
            <Head title="Hasil Simulasi" />

            <div className="w-full max-w-4xl rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-10 dark:border-slate-800 dark:bg-slate-900">
                {!showEdu ? (
                    <div className="mx-auto max-w-xl text-center">
                        <div className="mb-4 flex justify-center">
                            <div className="flex size-16 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                                <ShieldAlert className="size-8" />
                            </div>
                        </div>
                        <h1 className="mb-2 text-2xl font-bold text-slate-900 dark:text-slate-50">
                            Ini adalah Simulasi Phishing
                        </h1>
                        <p className="mb-8 text-slate-600 dark:text-slate-400">
                            Halaman yang baru saja Anda akses bukanlah situs
                            resmi. Ini adalah bagian dari penelitian untuk
                            meningkatkan kesadaran keamanan digital.
                        </p>

                        <div className="mb-8 text-left">{resultBox}</div>

                        {countdown !== null ? (
                            <div className="rounded-lg bg-blue-50 p-4 text-center border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">
                                <p className="mb-2 font-medium text-blue-800 dark:text-blue-300">
                                    Anda akan dialihkan secara otomatis ke Kuesioner Penelitian dalam:
                                </p>
                                <div className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                    {countdown} detik
                                </div>
                                <p className="mt-3 text-xs text-blue-600/70 dark:text-blue-400/70">
                                    Mohon tidak menutup halaman ini.
                                </p>
                            </div>
                        ) : (
                            <Button
                                onClick={() => setShowEdu(true)}
                                size="lg"
                                className="w-full bg-blue-600 hover:bg-blue-700"
                            >
                                Lihat Pembahasan Materi
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="animate-in duration-500 fade-in slide-in-from-bottom-4">
                        <header className="mb-8 border-b border-slate-200 pb-6 dark:border-slate-800">
                            <span className="mb-2 inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                Simulasi selesai · debrief & materi
                            </span>
                            <h1 className="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                                Edukasi Phishing
                            </h1>
                            <p className="mt-2 text-slate-600 dark:text-slate-400">
                                Pelajari lebih detail tentang apa yang baru saja
                                terjadi dan cara melindungi diri Anda.
                            </p>
                        </header>

                        <div className="grid gap-8 md:grid-cols-3">
                            <aside className="space-y-8 md:col-span-1">
                                <section>
                                    <h2 className="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-100">
                                        Apa yang terjadi?
                                    </h2>
                                    <ul className="space-y-3 text-sm text-slate-600 dark:text-slate-400">
                                        <li>
                                            <strong className="text-slate-900 dark:text-slate-200">
                                                Penyamaran institusi
                                            </strong>{' '}
                                            — email meniru Pusat Layanan
                                            Pembelajaran Digital dengan
                                            peringatan login tidak biasa.
                                        </li>
                                        <li>
                                            <strong className="text-slate-900 dark:text-slate-200">
                                                Domain & urgensi
                                            </strong>{' '}
                                            — tautan ke alamat palsu dan ancaman
                                            akun dinonaktifkan dalam 24 jam.
                                        </li>
                                        <li>
                                            <strong className="text-slate-900 dark:text-slate-200">
                                                Dua tombol
                                            </strong>{' '}
                                            — Anda dihadapkan pada pilihan
                                            Verifikasi (login palsu) vs Tolak.
                                            Di dunia nyata, selalu hubungi
                                            institusi secara langsung.
                                        </li>
                                    </ul>
                                </section>

                                <div className="rounded-xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-900/20">
                                    <h2 className="mb-2 text-sm font-semibold text-blue-900 dark:text-blue-300">
                                        Pernyataan Etika
                                    </h2>
                                    <p className="text-xs text-blue-800 dark:text-blue-400">
                                        Ini simulasi edukatif penelitian. Tidak
                                        ada data pribadi diambil; tujuan
                                        meningkatkan kesadaran digital tanpa
                                        menyalahkan siapa pun.
                                    </p>
                                </div>
                            </aside>

                            <div className="space-y-6 md:col-span-2">
                                <details
                                    className="group rounded-xl border border-slate-200 bg-slate-50/50 p-6 dark:border-slate-800 dark:bg-slate-900"
                                    open
                                >
                                    <summary className="flex cursor-pointer items-center text-lg font-semibold text-slate-900 marker:mr-2 dark:text-slate-100">
                                        1. Apa Itu Phishing?
                                    </summary>
                                    <div className="mt-4 space-y-3 text-slate-600 dark:text-slate-400">
                                        <p>
                                            <strong>Phishing</strong> adalah
                                            teknik penipuan digital di mana
                                            penyerang menyamar sebagai entitas
                                            terpercaya (bank, perusahaan
                                            teknologi, institusi resmi) untuk
                                            mencuri kata sandi, data kartu, atau
                                            informasi pribadi lainnya.
                                        </p>
                                        <p>
                                            Penyerang memakai email, pesan teks,
                                            atau tautan yang terlihat sah agar
                                            korban memberi data atau mengklik
                                            tautan berbahaya.
                                        </p>
                                    </div>
                                </details>

                                <details className="group rounded-xl border border-slate-200 bg-slate-50/50 p-6 dark:border-slate-800 dark:bg-slate-900">
                                    <summary className="flex cursor-pointer items-center text-lg font-semibold text-slate-900 marker:mr-2 dark:text-slate-100">
                                        2. Ciri-Ciri Phishing
                                    </summary>
                                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                        <div className="rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-900/10">
                                            <h3 className="font-semibold text-red-900 dark:text-red-300">
                                                URL mencurigakan
                                            </h3>
                                            <p className="mt-1 text-sm text-red-700 dark:text-red-400">
                                                Domain tidak dikenal, typo
                                                (g00gle.com), atau subdomain
                                                aneh.
                                            </p>
                                        </div>
                                        <div className="rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-900/10">
                                            <h3 className="font-semibold text-red-900 dark:text-red-300">
                                                Tekanan waktu
                                            </h3>
                                            <p className="mt-1 text-sm text-red-700 dark:text-red-400">
                                                Urgensi palsu: "Akun ditutup 24
                                                jam" atau "Segera verifikasi".
                                            </p>
                                        </div>
                                        <div className="rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-900/10">
                                            <h3 className="font-semibold text-red-900 dark:text-red-300">
                                                Pengirim tidak resmi
                                            </h3>
                                            <p className="mt-1 text-sm text-red-700 dark:text-red-400">
                                                Alamat email mirip institusi
                                                resmi tetapi bukan domain resmi.
                                            </p>
                                        </div>
                                        <div className="rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-900/10">
                                            <h3 className="font-semibold text-red-900 dark:text-red-300">
                                                Permintaan data sensitif
                                            </h3>
                                            <p className="mt-1 text-sm text-red-700 dark:text-red-400">
                                                Minta sandi/PIN lewat email —
                                                institusi resmi jarang melakukan
                                                ini.
                                            </p>
                                        </div>
                                    </div>
                                </details>

                                <details className="group rounded-xl border border-slate-200 bg-slate-50/50 p-6 dark:border-slate-800 dark:bg-slate-900">
                                    <summary className="flex cursor-pointer items-center text-lg font-semibold text-slate-900 marker:mr-2 dark:text-slate-100">
                                        3. Kesalahan Umum
                                    </summary>
                                    <ul className="mt-4 list-disc space-y-2 pl-5 text-slate-600 dark:text-slate-400">
                                        <li>
                                            <strong>Klik tanpa cek URL</strong>{' '}
                                            — tampilan familiar bukan jaminan
                                            aman.
                                        </li>
                                        <li>
                                            <strong>
                                                Terburu-buru merespons
                                            </strong>{' '}
                                            — urgensi palsu mencegah berpikir
                                            kritis.
                                        </li>
                                        <li>
                                            <strong>
                                                Tidak verifikasi sumber
                                            </strong>{' '}
                                            — logo/desain saja tidak cukup.
                                        </li>
                                    </ul>
                                </details>

                                <details className="group rounded-xl border border-slate-200 bg-slate-50/50 p-6 dark:border-slate-800 dark:bg-slate-900">
                                    <summary className="flex cursor-pointer items-center text-lg font-semibold text-slate-900 marker:mr-2 dark:text-slate-100">
                                        4. Cara Mengecek Tautan
                                    </summary>
                                    <ol className="mt-4 list-decimal space-y-2 pl-5 text-slate-600 dark:text-slate-400">
                                        <li>
                                            <strong>Hover URL</strong> — lihat
                                            alamat lengkap sebelum klik.
                                        </li>
                                        <li>
                                            <strong>Cek domain</strong> —
                                            pastikan domain resmi (mis.{' '}
                                            <code className="rounded bg-slate-200 px-1 dark:bg-slate-700">
                                                google.com
                                            </code>
                                            ).
                                        </li>
                                        <li>
                                            <strong>Waspada typo</strong> —
                                            perhatikan huruf/angka pengganti.
                                        </li>
                                        <li>
                                            <strong>HTTPS</strong> — wajib untuk
                                            situs sensitif.
                                        </li>
                                        <li>
                                            <strong>Gunakan tool</strong> —
                                            pindai URL sebelum membuka, contoh:{' '}
                                            <em>VirusTotal</em>,{' '}
                                            <em>URLVoid</em>,{' '}
                                            <em>Safe Browsing</em>.
                                        </li>
                                    </ol>
                                </details>
                            </div>
                        </div>

                        <div className="mt-12 flex flex-col items-center border-t border-slate-200 pt-8 dark:border-slate-800">
                            <p className="mb-6 text-center text-slate-600 dark:text-slate-400">
                                Terima kasih atas partisipasi Anda — kesadaran
                                phishing membuat internet lebih aman.
                            </p>

                            <p className="font-semibold text-center text-slate-800 dark:text-slate-200">
                                Simulasi dan kuesioner telah selesai. Anda dapat menutup halaman ini.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
