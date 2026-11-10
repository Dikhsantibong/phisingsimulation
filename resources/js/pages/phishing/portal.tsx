import { Head, router } from '@inertiajs/react';
import { GraduationCap, ShieldAlert, Eye, EyeOff } from 'lucide-react';
import { useRef, useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/Simulation/PortalBehaviorController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type ThemeContent = {
    portal_title: string;
    portal_subtitle: string;
    id_field_label: string;
    cta_primary_label: string;
    cta_secondary_label: string;
};

type Props = {
    token: string;
    content: ThemeContent;
};

export default function Portal({ token, content }: Props) {
    const [keystrokeDetected, setKeystrokeDetected] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [kelas, setKelas] = useState('');
    const [idLocked, setIdLocked] = useState(true);
    const [kelasLocked, setKelasLocked] = useState(true);
    const [passwordLocked, setPasswordLocked] = useState(true);
    const submitted = useRef(false);

    // Browser/password-manager autofill never dispatches real keyboard
    // events, only a synthetic `input`/`change` — so only onKeyDown counts
    // as "the respondent actually typed something" here.
    const markKeystroke = () => setKeystrokeDetected(true);

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
        <div className="flex min-h-screen items-center justify-center bg-blue-50/50 p-3 sm:p-4 dark:bg-slate-950">
            <Head title={content.portal_title} />

            <div className="w-full max-w-md rounded-2xl border border-blue-100 bg-white p-6 shadow-lg shadow-blue-100/40 sm:p-8 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div className="mb-6 flex flex-col items-center text-center">
                    <div className="mb-4 flex size-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-600/20">
                        <GraduationCap className="size-8" />
                    </div>
                    <h1 className="text-lg font-bold tracking-tight text-slate-900 sm:text-xl dark:text-slate-50">
                        {content.portal_title}
                    </h1>
                    <p className="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                        {content.portal_subtitle}
                    </p>
                </div>

                <form
                    className="flex flex-col gap-4"
                    autoComplete="off"
                    onSubmit={(e) => {
                        e.preventDefault();
                        respond('submit');
                    }}
                >
                    <div className="grid gap-2">
                        <Label
                            htmlFor="email"
                            className="text-slate-700 dark:text-slate-300"
                        >
                            {content.id_field_label}
                        </Label>
                        <Input
                            id="email"
                            name="id_field_x9k"
                            type="text"
                            autoComplete="off"
                            readOnly={idLocked}
                            onFocus={() => setIdLocked(false)}
                            onKeyDown={markKeystroke}
                            required
                            placeholder="nama@sekolah.sch.id"
                            className="border-blue-200 focus-visible:ring-blue-600 dark:border-slate-700"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label
                            htmlFor="kelas"
                            className="text-slate-700 dark:text-slate-300"
                        >
                            Kelas
                        </Label>
                        <Input
                            id="kelas"
                            name="kelas_field_x9k"
                            type="text"
                            autoComplete="off"
                            readOnly={kelasLocked}
                            onFocus={() => setKelasLocked(false)}
                            onKeyDown={markKeystroke}
                            required
                            placeholder="Contoh: X IPA 1"
                            value={kelas}
                            onChange={(e) => setKelas(e.target.value)}
                            className="border-blue-200 focus-visible:ring-blue-600 dark:border-slate-700"
                        />
                    </div>

                    <div className="relative grid gap-2">
                        <Label
                            htmlFor="password"
                            className="text-slate-600 dark:text-slate-300"
                        >
                            Kata Sandi
                        </Label>
                        <div className="relative">
                            <Input
                                id="password"
                                name="pw_field_x9k"
                                type={showPassword ? 'text' : 'password'}
                                required
                                autoComplete="new-password"
                                readOnly={passwordLocked}
                                onFocus={() => setPasswordLocked(false)}
                                onKeyDown={markKeystroke}
                                data-lpignore="true"
                                data-1p-ignore="true"
                                placeholder="••••••••"
                                className="border-blue-200 pr-10 focus-visible:ring-blue-600 dark:border-slate-700"
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                className="absolute top-1/2 right-3 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                            >
                                {showPassword ? (
                                    <EyeOff className="size-4" />
                                ) : (
                                    <Eye className="size-4" />
                                )}
                            </button>
                        </div>
                    </div>

                    <div className="mt-2 flex flex-col gap-3">
                        <Button
                            type="submit"
                            className="w-full bg-blue-600 hover:bg-blue-700"
                            disabled={processing}
                        >
                            {processing && <Spinner className="mr-2" />}
                            {content.cta_primary_label}
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => respond('reject')}
                            disabled={processing}
                            className="w-full border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            {content.cta_secondary_label}
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
