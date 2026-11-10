import { Head, router, usePage } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    invalid?: boolean;
};

export default function CampaignAccessCode({ invalid }: Props) {
    const { url } = usePage();
    const [code, setCode] = useState('');
    const [processing, setProcessing] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        const base = url.split('?')[0];
        router.visit(`${base}?code=${encodeURIComponent(code)}`, {
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 p-3 sm:p-4 dark:bg-slate-950">
            <Head title="Verifikasi Akses" />

            <div className="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-lg sm:p-8 dark:border-slate-800 dark:bg-slate-900">
                <div className="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <KeyRound className="size-8" />
                </div>
                <h1 className="mb-2 text-lg font-bold tracking-tight text-slate-900 sm:text-xl dark:text-slate-50">
                    Masukkan Kode Akses
                </h1>
                <p className="mb-6 text-sm text-slate-500 dark:text-slate-400">
                    Tautan ini memerlukan kode akses sebelum dapat dilanjutkan.
                </p>

                <form className="flex flex-col gap-4" onSubmit={submit}>
                    <div className="grid gap-2 text-left">
                        <Label htmlFor="code">Kode Akses</Label>
                        <Input
                            id="code"
                            value={code}
                            onChange={(e) => setCode(e.target.value)}
                            autoComplete="off"
                            required
                        />
                        {invalid && (
                            <p className="text-sm text-red-600 dark:text-red-400">
                                Kode akses tidak valid.
                            </p>
                        )}
                    </div>

                    <Button type="submit" disabled={processing}>
                        Lanjutkan
                    </Button>
                </form>
            </div>
        </div>
    );
}
