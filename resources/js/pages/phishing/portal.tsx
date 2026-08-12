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

/**
 * Fake "Portal Pembelajaran Digital" login page.
 *
 * ETHICAL GUARANTEE: the values typed into the email/password fields are NEVER
 * read or transmitted. The inputs are uncontrolled; we only flip a boolean
 * `keystrokeDetected` flag on the first input event, and submit only
 * { action, keystroke_detected } to the server.
 */
export default function Portal({ token }: Props) {
    const [keystrokeDetected, setKeystrokeDetected] = useState(false);
    const [processing, setProcessing] = useState(false);
    const submitted = useRef(false);

    const respond = (action: 'submit' | 'report') => {
        if (submitted.current) {
            return;
        }
        submitted.current = true;
        setProcessing(true);

        router.post(store.url({ respondent: token }), {
            action,
            keystroke_detected: keystrokeDetected,
        });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-neutral-100 p-4 dark:bg-neutral-950">
            <Head title="Portal Pembelajaran Digital" />

            <div className="w-full max-w-md rounded-2xl border border-neutral-200 bg-white p-8 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div className="mb-6 flex flex-col items-center text-center">
                    <div className="mb-3 flex size-12 items-center justify-center rounded-xl bg-blue-600 text-white">
                        <GraduationCap className="size-7" />
                    </div>
                    <h1 className="text-lg font-semibold text-neutral-900 dark:text-neutral-50">
                        Portal Pembelajaran Digital
                    </h1>
                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Masuk untuk memverifikasi akun Anda
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
                        <Label htmlFor="email">Email / NIS</Label>
                        <Input
                            id="email"
                            name="email"
                            type="text"
                            autoComplete="off"
                            placeholder="nama@sekolah.sch.id"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Kata Sandi</Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            autoComplete="off"
                            placeholder="••••••••"
                        />
                    </div>

                    <Button
                        type="submit"
                        className="mt-2 w-full"
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        Masuk
                    </Button>
                </form>

                <div className="mt-6 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                    <button
                        type="button"
                        onClick={() => respond('report')}
                        disabled={processing}
                        className="flex w-full items-center justify-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-100 disabled:opacity-50 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300"
                    >
                        <ShieldAlert className="size-4" />
                        Laporkan sebagai Mencurigakan
                    </button>
                    <p className="mt-2 text-center text-xs text-neutral-400">
                        Merasa email ini mencurigakan? Jangan masukkan data
                        Anda.
                    </p>
                </div>
            </div>
        </div>
    );
}
