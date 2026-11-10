import { Head, router } from '@inertiajs/react';
import { Copy, MessageSquareText, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Swal from 'sweetalert2';
import { Badge } from '@/components/ui/badge';
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
import { index as campaignsIndex } from '@/routes/campaigns';

type Option = { value: string; label: string };

type Campaign = {
    id: number;
    slug: string;
    title: string;
    theme: string;
    themes: string[];
    theme_label: string;
    is_multi_theme: boolean;
    distribution_mode: string;
    is_active: boolean;
    expires_at: string | null;
    access_code: string | null;
    respondents_count: number;
    public_url: string | null;
    broadcast_message: string | null;
};

type Props = {
    campaigns: Campaign[];
    themes: Option[];
    distributionModes: Option[];
};

export default function CampaignsIndex({
    campaigns,
    themes,
    distributionModes,
}: Props) {
    const [title, setTitle] = useState('');
    const [selectedThemes, setSelectedThemes] = useState<string[]>(
        themes[0] ? [themes[0].value] : [],
    );
    const [distributionMode, setDistributionMode] = useState(
        distributionModes[0]?.value ?? '',
    );
    const [accessCode, setAccessCode] = useState('');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const toggleTheme = (value: string) => {
        setSelectedThemes((prev) =>
            prev.includes(value)
                ? prev.filter((v) => v !== value)
                : [...prev, value],
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (selectedThemes.length === 0) {
            setErrors({ themes: 'Pilih minimal satu tema.' });

            return;
        }

        setProcessing(true);
        setErrors({});

        router.post(
            campaignsIndex.url(),
            {
                title,
                themes: selectedThemes,
                distribution_mode: distributionMode,
                access_code: accessCode || null,
            },
            {
                onError: (err) => setErrors(err as Record<string, string>),
                onSuccess: () => {
                    setTitle('');
                    setAccessCode('');
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    const destroy = (campaign: Campaign) => {
        const isDark = document.documentElement.classList.contains('dark');

        Swal.fire({
            title: 'Hapus Kampanye?',
            text: 'Responden yang sudah ada tidak terhapus, hanya ditautkan menjadi tanpa kampanye.',
            icon: 'warning',
            showCancelButton: true,
            background: isDark ? '#18181b' : '#ffffff',
            color: isDark ? '#f8fafc' : '#0f172a',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: isDark ? '#334155' : '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
        }).then((res) => {
            if (res.isConfirmed) {
                router.delete(`/campaigns/${campaign.id}`);
            }
        });
    };

    return (
        <>
            <Head title="Kampanye Simulasi" />
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Buat Kampanye Baru
                        </CardTitle>
                        <CardDescription>
                            Kampanye "Link Publik" bisa disebar lewat satu
                            tautan tanpa perlu email/no. WA responden. Kampanye
                            "Email/WA Manual" dipakai saat kirim simulasi ke
                            daftar responden yang sudah diketahui kontaknya.
                            Pilih lebih dari satu tema agar setiap responden
                            mendapat tema yang diacak secara individual, bukan
                            tema yang sama untuk semua orang.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid gap-4 sm:grid-cols-2"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="title">Judul Kampanye</Label>
                                <Input
                                    id="title"
                                    value={title}
                                    onChange={(e) => setTitle(e.target.value)}
                                    placeholder="Contoh: Gelombang 1 - Kelas XII"
                                    required
                                />
                                {errors['title'] && (
                                    <p className="text-sm text-red-600 dark:text-red-400">
                                        {errors['title']}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label>Tema</Label>
                                <div className="flex flex-col gap-2 rounded-md border border-input px-3 py-2">
                                    {themes.map((t) => (
                                        <label
                                            key={t.value}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={selectedThemes.includes(
                                                    t.value,
                                                )}
                                                onChange={() =>
                                                    toggleTheme(t.value)
                                                }
                                                className="size-4"
                                            />
                                            {t.label}
                                        </label>
                                    ))}
                                </div>
                                {selectedThemes.length > 1 && (
                                    <p className="text-xs text-muted-foreground">
                                        {selectedThemes.length} tema dipilih —
                                        tiap responden akan diberi salah satunya
                                        secara acak.
                                    </p>
                                )}
                                {errors['themes'] && (
                                    <p className="text-sm text-red-600 dark:text-red-400">
                                        {errors['themes']}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="distribution_mode">
                                    Mode Distribusi
                                </Label>
                                <select
                                    id="distribution_mode"
                                    value={distributionMode}
                                    onChange={(e) =>
                                        setDistributionMode(e.target.value)
                                    }
                                    className="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    {distributionModes.map((m) => (
                                        <option key={m.value} value={m.value}>
                                            {m.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="access_code">
                                    Kode Akses (opsional, untuk Link Publik)
                                </Label>
                                <Input
                                    id="access_code"
                                    value={accessCode}
                                    onChange={(e) =>
                                        setAccessCode(e.target.value)
                                    }
                                    placeholder="Kosongkan jika tidak perlu"
                                />
                            </div>

                            <div className="sm:col-span-2">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner className="mr-2" />}
                                    Buat Kampanye
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Daftar Kampanye
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {campaigns.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Belum ada kampanye.
                            </p>
                        )}

                        {campaigns.map((c) => (
                            <div
                                key={c.id}
                                className="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-medium">
                                            {c.title}
                                        </span>
                                        <Badge variant="secondary">
                                            {c.theme_label}
                                        </Badge>
                                        <Badge
                                            variant={
                                                c.is_active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                        >
                                            {c.is_active ? 'Aktif' : 'Nonaktif'}
                                        </Badge>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {c.respondents_count} responden
                                        {c.access_code && ' · kode akses aktif'}
                                    </p>
                                    {c.public_url && (
                                        <div className="flex items-center gap-2 text-xs">
                                            <code className="rounded bg-muted px-2 py-1">
                                                {c.public_url}
                                            </code>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    navigator.clipboard.writeText(
                                                        c.public_url as string,
                                                    )
                                                }
                                                className="text-muted-foreground hover:text-foreground"
                                                title="Salin tautan"
                                            >
                                                <Copy className="size-3.5" />
                                            </button>
                                            {c.broadcast_message && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        navigator.clipboard.writeText(
                                                            c.broadcast_message as string,
                                                        )
                                                    }
                                                    className="inline-flex items-center gap-1 text-muted-foreground hover:text-foreground"
                                                    title="Salin pesan WA/broadcast siap kirim (link sudah termasuk)"
                                                >
                                                    <MessageSquareText className="size-3.5" />
                                                    Salin Pesan
                                                </button>
                                            )}
                                        </div>
                                    )}
                                </div>

                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.put(
                                                `/campaigns/${c.id}/toggle-active`,
                                            )
                                        }
                                    >
                                        {c.is_active
                                            ? 'Nonaktifkan'
                                            : 'Aktifkan'}
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/50"
                                        onClick={() => destroy(c)}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CampaignsIndex.layout = {
    breadcrumbs: [{ title: 'Kampanye', href: campaignsIndex() }],
};
