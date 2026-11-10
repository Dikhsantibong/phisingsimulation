import { Head, router } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    Database,
    Download,
    ShieldCheck,
    Upload,
} from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    download,
    downloadScored,
    importTally,
    index as exportIndex,
} from '@/routes/export';

type Props = {
    total: number;
    pendingQuestionnaire: number;
};

export default function DataExport({ total, pendingQuestionnaire }: Props) {
    const [anonymise, setAnonymise] = useState(true);
    const [importing, setImporting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const query = anonymise ? { anonymise: 1 } : {};
    const rawUrl = download.url({ query });
    const scoredUrl = downloadScored.url({ query });

    const submitImport = (e: React.FormEvent) => {
        e.preventDefault();
        const file = fileInputRef.current?.files?.[0];

        if (!file) {
            return;
        }

        setImporting(true);
        router.post(
            importTally.url(),
            { file },
            {
                forceFormData: true,
                onFinish: () => {
                    setImporting(false);

                    if (fileInputRef.current) {
                        fileInputRef.current.value = '';
                    }
                },
            },
        );
    };

    return (
        <>
            <Head title="Ekspor Data" />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                {pendingQuestionnaire > 0 && (
                    <div className="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                        <div>
                            <span className="font-medium">
                                {pendingQuestionnaire} responden
                            </span>{' '}
                            sudah menyelesaikan simulasi tapi belum ada jawaban
                            kuesionernya — kolom skor (Total
                            Knowledge/Attitude/Behavior) untuk mereka akan
                            kosong sampai Anda impor CSV/Excel dari Tally di
                            bawah ini.
                        </div>
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Upload className="size-4" /> Impor Jawaban
                            Kuesioner dari Tally
                        </CardTitle>
                        <CardDescription>
                            Kalau webhook Tally tidak sampai ke server (mis.
                            dites secara lokal, atau integrasi Tally sedang
                            bermasalah), unduh CSV langsung dari dashboard Tally
                            lalu unggah di sini. Sistem mencocokkan tiap baris
                            ke responden lewat kolom{' '}
                            <code className="rounded bg-muted px-1 py-0.5 text-xs">
                                session_token
                            </code>
                            .
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={submitImport}
                            className="flex flex-col gap-3 sm:flex-row sm:items-center"
                        >
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".csv,text/csv"
                                required
                                className="flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-muted file:px-2 file:py-1 file:text-sm"
                            />
                            <Button type="submit" disabled={importing}>
                                {importing && <Spinner className="mr-2" />}
                                Impor CSV
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Database className="size-4" /> Ekspor Dataset
                        </CardTitle>
                        <CardDescription>
                            Menggabungkan data responden, event simulasi,
                            reminder, dan jawaban kuesioner KAB menjadi satu
                            file CSV.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="rounded-lg border bg-muted/30 p-4 text-sm">
                            <span className="text-2xl font-semibold tabular-nums">
                                {total}
                            </span>{' '}
                            <span className="text-muted-foreground">
                                responden akan diekspor
                            </span>
                        </div>

                        <label className="flex items-start gap-3 rounded-lg border p-4">
                            <Checkbox
                                checked={anonymise}
                                onCheckedChange={(v) =>
                                    setAnonymise(Boolean(v))
                                }
                                className="mt-0.5"
                            />
                            <div>
                                <div className="flex items-center gap-2 font-medium">
                                    <ShieldCheck className="size-4 text-emerald-600" />
                                    Anonimisasi (rekomendasi)
                                </div>
                                <Label className="font-normal text-muted-foreground">
                                    Kecualikan nama, email, dan nomor WA — hanya
                                    sisakan session token sebagai pengenal.
                                </Label>
                            </div>
                        </label>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <Button asChild size="lg">
                                <a href={rawUrl} download>
                                    <Download className="size-4" />
                                    Dataset Mentah
                                </a>
                            </Button>
                            <Button asChild size="lg" variant="outline">
                                <a href={scoredUrl} download>
                                    <BarChart3 className="size-4" />
                                    Dataset Skor (K/A/B/Simulasi)
                                </a>
                            </Button>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            <strong>Dataset Mentah</strong>: setiap pertanyaan
                            jadi kolom sendiri, beserta semua data perilaku dan
                            waktu sistem — untuk audit/seleksi manual per
                            pertanyaan. <strong>Dataset Skor</strong>: sudah
                            dihitung jadi Total Knowledge, Total Attitude, Total
                            Behavior, Skor Simulasi, dan Tingkat Kesadaran akhir
                            — siap untuk klasifikasi Random Forest.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

DataExport.layout = {
    breadcrumbs: [{ title: 'Ekspor Data', href: exportIndex() }],
};
