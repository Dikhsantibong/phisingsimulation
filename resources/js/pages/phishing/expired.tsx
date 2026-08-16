import { Head } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';

export default function Expired() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 p-4 dark:bg-slate-950">
            <Head title="Waktu Habis" />

            <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-lg dark:border-slate-800 dark:bg-slate-900">
                <div className="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                    <AlertCircle className="size-8" />
                </div>
                <h1 className="mb-2 text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                    Tautan Kedaluwarsa
                </h1>
                <p className="text-slate-500 dark:text-slate-400">
                    Batas waktu respon untuk simulasi ini telah berakhir. Anda tidak dapat lagi mengakses halaman ini.
                </p>
            </div>
        </div>
    );
}
