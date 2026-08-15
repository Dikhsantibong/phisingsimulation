import { Head, router } from '@inertiajs/react';
import { GraduationCap, ShieldAlert } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/actions/App/Http/Controllers/Simulation/PortalBehaviorController';

type Props = {
    token: string;
};

export default function Portal({ token }: Props) {
    const [keystrokeDetected, setKeystrokeDetected] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [kelas, setKelas] = useState('');
    const submitted = useRef(false);

    const respond = (action: 'submit' | 'report' | 'reject') => {
        if (submitted.current) {
            return;
        }
        submitted.current = true;
        setProcessing(true);

        router.post(store.url({ respondent: token }), {
            action,
            keystroke_detected: keystrokeDetected,
            kelas,
        });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-blue-50/50 p-4 dark:bg-slate-950">
            <Head title="Portal Pembelajaran Digital" />

            <div className="w-full max-w-md rounded-2xl border border-blue-100 bg-white p-8 shadow-lg shadow-blue-100/40 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div className="mb-6 flex flex-col items-center text-center">
                    <div className="mb-4 flex size-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-600/20">
                        <GraduationCap className="size-8" />
                    </div>
                    <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                        Sistem Pembelajaran Digital
                    </h1>
                    <p className="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                        Masuk untuk memverifikasi identitas Anda
                    </p>
                </div>

                <form
                    className="flex flex-col gap-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        respond('submit');
                    }}
                    onInput={() => setKeystrokeDetected(true)}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="email" className="text-slate-700 dark:text-slate-300">Email atau NIS</Label>
                        <Input
                            id="email"
                            name="email"
                            type="text"
                            autoComplete="off"
                            required
                            placeholder="nama@sekolah.sch.id"
                            className="border-blue-200 focus-visible:ring-blue-600 dark:border-slate-700"
                        />
                    </div>
                    
                    <div className="grid gap-2">
                        <Label htmlFor="kelas" className="text-slate-700 dark:text-slate-300">Kelas</Label>
                        <Input
                            id="kelas"
                            name="kelas"
                            type="text"
                            autoComplete="off"
                            required
                            placeholder="Contoh: X IPA 1"
                            value={kelas}
                            onChange={(e) => setKelas(e.target.value)}
                            className="border-blue-200 focus-visible:ring-blue-600 dark:border-slate-700"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password" className="text-slate-700 dark:text-slate-300">Kata Sandi</Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autoComplete="off"
                            placeholder="••••••••"
                            className="border-blue-200 focus-visible:ring-blue-600 dark:border-slate-700"
                        />
                    </div>

                    <div className="mt-2 flex flex-col gap-3">
                        <Button
                            type="submit"
                            className="w-full bg-blue-600 hover:bg-blue-700"
                            disabled={processing}
                        >
                            {processing && <Spinner className="mr-2" />}
                            Verifikasi Akun
                        </Button>
                        
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => respond('reject')}
                            disabled={processing}
                            className="w-full border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Tolak Aktivitas Ini
                        </Button>
                    </div>
                </form>

                <div className="mt-8 flex justify-center border-t border-slate-100 pt-6 dark:border-slate-800">
                    <button
                        type="button"
                        onClick={() => respond('report')}
                        disabled={processing}
                        className="flex items-center gap-1.5 text-xs font-medium text-slate-400 transition hover:text-slate-600 disabled:opacity-50 dark:text-slate-500 dark:hover:text-slate-300"
                    >
                        <ShieldAlert className="size-3.5" />
                        Laporkan sebagai Mencurigakan
                    </button>
                </div>
            </div>
        </div>
    );
}
