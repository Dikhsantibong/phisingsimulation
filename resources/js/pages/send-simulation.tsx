import { Form, Head, router } from '@inertiajs/react';
import { KeyRound, Send, Trash2, Upload } from 'lucide-react';
import { useMemo, useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/Researcher/SendSimulationController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update as updateResearchKey } from '@/routes/research-key';
import { create as sendSimulationCreate } from '@/routes/send-simulation';

type Row = {
    name: string;
    class_group: string;
    email: string;
    whatsapp_number: string;
};

type Props = {
    hasResearchKey: boolean;
};

/** Parse pasted/uploaded CSV: columns name, class_group, email, whatsapp_number. */
function parseCsv(text: string): Row[] {
    return text
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line) => line.split(/[,;\t]/).map((c) => c.trim()))
        .filter((cols) => cols.some((c) => c.includes('@')))
        .map((cols) => {
            const email = cols.find((c) => c.includes('@')) ?? '';
            const rest = cols.filter((c) => c !== email);

            return {
                name: rest[0] ?? '',
                class_group: rest[1] ?? '',
                email,
                whatsapp_number: rest[2] ?? '',
            };
        });
}

export default function SendSimulation({ hasResearchKey }: Props) {
    const [rows, setRows] = useState<Row[]>([]);
    const [researchKey, setResearchKey] = useState('');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    // Default null means no limit
    const [timeLimitValue, setTimeLimitValue] = useState<string>('');
    const [timeLimitUnit, setTimeLimitUnit] = useState<'minutes' | 'hours'>(
        'minutes',
    );

    const emptyRow: Row = {
        name: '',
        class_group: '',
        email: '',
        whatsapp_number: '',
    };
    const validRows = useMemo(() => rows.filter((r) => r.email), [rows]);

    const handleFile = (file: File) => {
        const reader = new FileReader();
        reader.onload = () =>
            setRows((prev) => [...prev, ...parseCsv(String(reader.result))]);
        reader.readAsText(file);
    };

    const updateRow = (i: number, key: keyof Row, value: string) => {
        setRows((prev) =>
            prev.map((r, idx) => (idx === i ? { ...r, [key]: value } : r)),
        );
    };

    const submit = () => {
        setProcessing(true);
        setErrors({});
        router.post(
            store.url(),
            {
                research_key: researchKey,
                respondents: validRows,
                time_limit_value: timeLimitValue
                    ? Number(timeLimitValue)
                    : null,
                time_limit_unit: timeLimitUnit,
            },
            {
                onError: (e) => setErrors(e as Record<string, string>),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title="Kirim Simulasi" />
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-4 p-4">
                {/* Research key missing alert */}
                {!hasResearchKey && (
                    <Card className="border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/20">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base text-red-800 dark:text-red-300">
                                <KeyRound className="size-4" /> Perhatian:
                                Research Key Belum Diatur
                            </CardTitle>
                            <CardDescription className="text-red-700 dark:text-red-400">
                                Anda wajib mengatur Research Key melalui menu{' '}
                                <a
                                    href="/settings/security"
                                    className="font-semibold underline"
                                >
                                    Pengaturan Keamanan
                                </a>{' '}
                                terlebih dahulu sebelum bisa memulai simulasi.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                )}

                {/* Respondent list */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Daftar Responden
                        </CardTitle>
                        <CardDescription>
                            Impor CSV (kolom: nama, kelas, email, no. WA) atau
                            tambah manual.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-wrap gap-2">
                            <label className="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-input bg-background px-3 py-2 text-sm font-medium hover:bg-accent">
                                <Upload className="size-4" />
                                Impor CSV
                                <input
                                    type="file"
                                    accept=".csv,text/csv,text/plain"
                                    className="hidden"
                                    onChange={(e) =>
                                        e.target.files?.[0] &&
                                        handleFile(e.target.files[0])
                                    }
                                />
                            </label>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    setRows((p) => [...p, { ...emptyRow }])
                                }
                            >
                                + Tambah baris
                            </Button>
                        </div>

                        {rows.length > 0 && (
                            <div className="overflow-x-auto rounded-lg border">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/50 text-left text-muted-foreground">
                                        <tr>
                                            <th className="p-2 font-medium">
                                                Nama
                                            </th>
                                            <th className="p-2 font-medium">
                                                Kelas
                                            </th>
                                            <th className="p-2 font-medium">
                                                Email *
                                            </th>
                                            <th className="p-2 font-medium">
                                                No. WA
                                            </th>
                                            <th className="p-2" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.map((row, i) => (
                                            <tr key={i} className="border-t">
                                                {(
                                                    [
                                                        'name',
                                                        'class_group',
                                                        'email',
                                                        'whatsapp_number',
                                                    ] as const
                                                ).map((key) => (
                                                    <td
                                                        key={key}
                                                        className="p-1"
                                                    >
                                                        <Input
                                                            value={row[key]}
                                                            onChange={(e) =>
                                                                updateRow(
                                                                    i,
                                                                    key,
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            className="h-8"
                                                        />
                                                    </td>
                                                ))}
                                                <td className="p-1 text-right">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            setRows((p) =>
                                                                p.filter(
                                                                    (_, idx) =>
                                                                        idx !==
                                                                        i,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4 text-destructive" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        {errors['respondents'] && (
                            <InputError message={errors['respondents']} />
                        )}
                    </CardContent>
                </Card>

                {/* Send */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Konfigurasi & Pengiriman
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div className="grid flex-1 gap-2">
                                <Label htmlFor="time_limit_value">
                                    Batas Waktu Respon (Opsional)
                                </Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="time_limit_value"
                                        type="number"
                                        min="1"
                                        placeholder="Contoh: 30"
                                        value={timeLimitValue}
                                        onChange={(e) =>
                                            setTimeLimitValue(e.target.value)
                                        }
                                        className="w-full"
                                    />
                                    <select
                                        value={timeLimitUnit}
                                        onChange={(e) =>
                                            setTimeLimitUnit(
                                                e.target.value as
                                                    'minutes' | 'hours',
                                            )
                                        }
                                        className="rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                    >
                                        <option value="minutes">Menit</option>
                                        <option value="hours">Jam</option>
                                    </select>
                                </div>
                                <InputError
                                    message={errors['time_limit_value']}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Kosongkan jika tidak ada batas waktu.
                                </p>
                            </div>
                        </div>
                        <div className="flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-end">
                            <div className="grid flex-1 gap-2">
                                <Label htmlFor="send_research_key">
                                    Research key (konfirmasi pengiriman)
                                </Label>
                                <Input
                                    id="send_research_key"
                                    type="password"
                                    autoComplete="off"
                                    value={researchKey}
                                    onChange={(e) =>
                                        setResearchKey(e.target.value)
                                    }
                                    disabled={!hasResearchKey}
                                />
                                <InputError message={errors['research_key']} />
                            </div>
                            <div className="text-sm text-muted-foreground sm:pb-2">
                                <span className="font-semibold text-foreground tabular-nums">
                                    {validRows.length}
                                </span>{' '}
                                responden valid siap dikirim
                            </div>
                            <Button
                                type="button"
                                onClick={submit}
                                disabled={
                                    processing ||
                                    !hasResearchKey ||
                                    validRows.length === 0
                                }
                            >
                                {processing ? (
                                    <Spinner />
                                ) : (
                                    <Send className="size-4" />
                                )}
                                Kirim Simulasi
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

SendSimulation.layout = {
    breadcrumbs: [{ title: 'Kirim Simulasi', href: sendSimulationCreate() }],
};
