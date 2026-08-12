import { Head } from '@inertiajs/react';
import { Database, Download, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
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
import { download, index as exportIndex } from '@/routes/export';

type Props = {
    total: number;
};

export default function DataExport({ total }: Props) {
    const [anonymise, setAnonymise] = useState(true);

    const url = download.url({ query: anonymise ? { anonymise: 1 } : {} });

    return (
        <>
            <Head title="Ekspor Data" />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Database className="size-4" /> Ekspor Dataset
                            Gabungan
                        </CardTitle>
                        <CardDescription>
                            Menggabungkan data responden, event simulasi,
                            reminder, dan jawaban kuesioner KAB menjadi satu
                            file CSV siap pakai untuk notebook Random Forest.
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

                        <Button asChild className="w-full" size="lg">
                            <a href={url} download>
                                <Download className="size-4" />
                                Unduh CSV
                            </a>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

DataExport.layout = {
    breadcrumbs: [{ title: 'Ekspor Data', href: exportIndex() }],
};
