import { Head } from '@inertiajs/react';
import { BadgeCheck, BookOpen, Eye, Lock, ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';

type Props = {
    token: string;
    questionnaireUrl: string | null;
};

const tips = [
    {
        icon: Eye,
        title: 'Periksa alamat pengirim',
        body: 'Email phishing sering memakai domain yang mirip tapi tidak resmi. Perhatikan ejaan dan domain dengan teliti.',
    },
    {
        icon: Lock,
        title: 'Jangan buru-buru memasukkan sandi',
        body: 'Ancaman "akun akan dinonaktifkan" adalah taktik menekan agar Anda panik dan lengah.',
    },
    {
        icon: ShieldCheck,
        title: 'Verifikasi lewat kanal resmi',
        body: 'Bila ragu, buka situs resmi secara manual — jangan klik tautan di dalam email.',
    },
];

/**
 * Mandatory debrief / reveal shown after the respondent interacts with the
 * fake portal, before continuing to the KAB questionnaire.
 */
export default function Reveal({ questionnaireUrl }: Props) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-neutral-100 p-4 dark:bg-neutral-950">
            <Head title="Ini Simulasi Edukatif" />

            <div className="w-full max-w-xl rounded-2xl border border-neutral-200 bg-white p-8 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div className="mb-6 flex flex-col items-center text-center">
                    <div className="mb-3 flex size-12 items-center justify-center rounded-xl bg-emerald-600 text-white">
                        <BadgeCheck className="size-7" />
                    </div>
                    <h1 className="text-xl font-semibold text-neutral-900 dark:text-neutral-50">
                        Ini adalah simulasi edukatif — bukan serangan nyata
                    </h1>
                    <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                        Email dan halaman login tadi adalah bagian dari
                        penelitian kesadaran keamanan informasi.{' '}
                        <strong>
                            Tidak ada data apa pun yang Anda ketik yang
                            disimpan.
                        </strong>{' '}
                        Tujuannya untuk mengukur bagaimana kita menanggapi upaya
                        phishing, agar bisa lebih waspada ke depannya.
                    </p>
                </div>

                <div className="mb-6 space-y-3">
                    {tips.map((tip) => (
                        <div
                            key={tip.title}
                            className="flex gap-3 rounded-xl border border-neutral-200 p-3 dark:border-neutral-800"
                        >
                            <tip.icon className="mt-0.5 size-5 shrink-0 text-blue-600 dark:text-blue-400" />
                            <div>
                                <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                    {tip.title}
                                </p>
                                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                    {tip.body}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>

                {questionnaireUrl ? (
                    <Button asChild className="w-full" size="lg">
                        <a href={questionnaireUrl}>
                            <BookOpen className="size-4" />
                            Lanjut ke Kuesioner
                        </a>
                    </Button>
                ) : (
                    <div className="rounded-lg border border-amber-300 bg-amber-50 p-3 text-center text-sm text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300">
                        Tautan kuesioner belum dikonfigurasi. Hubungi peneliti.
                    </div>
                )}

                <p className="mt-4 text-center text-xs text-neutral-400">
                    Terima kasih telah berpartisipasi dalam penelitian ini.
                </p>
            </div>
        </div>
    );
}
