import { Head, Link, router } from '@inertiajs/react';
import { MessageCircle, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Swal from 'sweetalert2';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { bulkDestroy, index as remindersIndex, wa } from '@/routes/reminders';

type ReminderTypeValue =
    | 'simulasi_diabaikan'
    | 'portal_diabaikan'
    | 'kuesioner_belum_selesai'
    | 'batas_waktu_terlewat';

type Reminder = {
    id: number;
    token: string;
    respondent: string;
    class_group: string;
    reminder_type: ReminderTypeValue;
    reminder_type_label: string;
    phone_number: string;
    scheduled_at: string;
    sent_at: string | null;
    followed_up_at: string | null;
};

type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

type Props = {
    reminders: Paginator<Reminder>;
};

function fmt(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleString('id-ID', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

const typeBadgeClass: Record<ReminderTypeValue, string> = {
    simulasi_diabaikan:
        'border-red-500 bg-red-50 text-red-700 dark:bg-red-900/50 dark:text-red-400',
    portal_diabaikan:
        'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400',
    kuesioner_belum_selesai:
        'border-orange-500 bg-orange-50 text-orange-700 dark:bg-orange-900/50 dark:text-orange-400',
    batas_waktu_terlewat:
        'border-rose-600 bg-rose-50 text-rose-700 dark:bg-rose-900/50 dark:text-rose-400',
};

const resendAction: Record<
    ReminderTypeValue,
    { confirmText: string; buttonLabel: string }
> = {
    simulasi_diabaikan: {
        confirmText:
            'Sistem akan mengirim ulang peringatan keamanan palsu (pesan simulasi) ke WhatsApp responden.',
        buttonLabel: 'Kirim Ulang Simulasi',
    },
    portal_diabaikan: {
        confirmText:
            'Sistem akan mengirim ulang peringatan keamanan palsu (pesan simulasi) ke WhatsApp responden.',
        buttonLabel: 'Kirim Ulang Simulasi',
    },
    kuesioner_belum_selesai: {
        confirmText:
            'Sistem akan mengirim link kuesioner ke WhatsApp responden.',
        buttonLabel: 'Kirim Kuesioner',
    },
    batas_waktu_terlewat: {
        confirmText:
            'Sistem akan mengirim link kuesioner ke WhatsApp responden.',
        buttonLabel: 'Kirim Kuesioner',
    },
};

export default function RemindersIndex({ reminders }: Props) {
    const resend = (reminder: Reminder) => {
        const { confirmText } = resendAction[reminder.reminder_type];
        const isDark =
            document.documentElement.classList.contains('dark');

        Swal.fire({
            title: 'Kirim WhatsApp?',
            text: confirmText,
            icon: 'question',
            showCancelButton: true,
            background: isDark ? '#18181b' : '#ffffff',
            color: isDark ? '#f8fafc' : '#0f172a',
            confirmButtonColor: '#059669',
            cancelButtonColor: isDark ? '#334155' : '#64748b',
            confirmButtonText: 'Ya, Kirim',
            cancelButtonText: 'Batal',
        }).then((res) => {
            if (res.isConfirmed) {
                router.post(
                    wa.url({ reminder: reminder.id }),
                    {},
                    { preserveScroll: true },
                );
            }
        });
    };

    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const allSelected =
        reminders.data.length > 0 &&
        reminders.data.every((r) => selectedIds.includes(r.id));

    const toggleAll = () => {
        setSelectedIds(allSelected ? [] : reminders.data.map((r) => r.id));
    };

    const toggleOne = (id: number) => {
        setSelectedIds((prev) =>
            prev.includes(id)
                ? prev.filter((x) => x !== id)
                : [...prev, id],
        );
    };

    const confirmAndDelete = (
        title: string,
        text: string,
        onConfirm: () => void,
    ) => {
        const isDark = document.documentElement.classList.contains('dark');

        Swal.fire({
            title,
            text,
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
                onConfirm();
            }
        });
    };

    const deleteSelected = () => {
        confirmAndDelete(
            `Hapus ${selectedIds.length} Reminder?`,
            'Entri reminder yang dipilih akan dihapus. Jika kondisinya masih berlaku, reminder bisa muncul lagi nanti.',
            () =>
                router.delete(bulkDestroy.url(), {
                    data: { ids: selectedIds },
                    onSuccess: () => setSelectedIds([]),
                }),
        );
    };

    return (
        <>
            <Head title="Reminder" />
            <div className="flex flex-col gap-4 p-4">
                <p className="text-sm text-muted-foreground">
                    Responden yang sudah mengisi kuesioner otomatis hilang
                    dari daftar ini — tidak perlu ditandai manual.
                </p>

                {selectedIds.length > 0 && (
                    <div className="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm dark:border-red-900/50 dark:bg-red-950/20">
                        <span className="text-red-700 dark:text-red-400">
                            {selectedIds.length} reminder dipilih
                        </span>
                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={deleteSelected}
                        >
                            <Trash2 className="size-4" />
                            Hapus yang Dipilih
                        </Button>
                    </div>
                )}

                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left text-muted-foreground">
                                    <tr>
                                        <th className="w-10 p-3">
                                            <input
                                                type="checkbox"
                                                checked={allSelected}
                                                onChange={toggleAll}
                                                aria-label="Pilih semua"
                                            />
                                        </th>
                                        <th className="p-3 font-medium">
                                            Responden
                                        </th>
                                        <th className="p-3 font-medium">
                                            Jenis Pengingat
                                        </th>
                                        <th className="p-3 font-medium">
                                            No HP
                                        </th>
                                        <th className="p-3 font-medium">
                                            Dijadwalkan
                                        </th>
                                        <th className="p-3 font-medium">
                                            Notifikasi ke Peneliti
                                        </th>
                                        <th className="p-3 font-medium">
                                            Sudah Dikirim Ulang?
                                        </th>
                                        <th className="p-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {reminders.data.map((r) => (
                                        <tr
                                            key={r.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="p-3">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedIds.includes(
                                                        r.id,
                                                    )}
                                                    onChange={() =>
                                                        toggleOne(r.id)
                                                    }
                                                    aria-label={`Pilih ${r.respondent}`}
                                                />
                                            </td>
                                            <td className="p-3">
                                                <div className="font-medium">
                                                    {r.respondent}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {r.class_group}
                                                </div>
                                            </td>
                                            <td className="p-3">
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        typeBadgeClass[
                                                            r.reminder_type
                                                        ]
                                                    }
                                                >
                                                    {r.reminder_type_label}
                                                </Badge>
                                            </td>
                                            <td className="p-3">
                                                {r.phone_number}
                                            </td>
                                            <td className="p-3 text-muted-foreground">
                                                {fmt(r.scheduled_at)}
                                            </td>
                                            <td className="p-3">
                                                {r.sent_at ? (
                                                    <Badge variant="secondary">
                                                        {fmt(r.sent_at)}
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline">
                                                        Di antrean
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="p-3">
                                                {r.followed_up_at ? (
                                                    <Badge className="bg-emerald-600">
                                                        {fmt(
                                                            r.followed_up_at,
                                                        )}
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        Belum
                                                    </span>
                                                )}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    size="sm"
                                                    variant="default"
                                                    className="bg-emerald-600 hover:bg-emerald-700"
                                                    onClick={() =>
                                                        resend(r)
                                                    }
                                                >
                                                    <MessageCircle className="size-4" />
                                                    {
                                                        resendAction[
                                                            r.reminder_type
                                                        ].buttonLabel
                                                    }
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                    {reminders.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={8}
                                                className="p-8 text-center text-muted-foreground"
                                            >
                                                Belum ada reminder terjadwal.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex flex-wrap justify-end gap-1 text-sm">
                    {reminders.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            preserveScroll
                            className={`rounded-md px-3 py-1 ${
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : link.url
                                      ? 'hover:bg-accent'
                                      : 'pointer-events-none opacity-40'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            </div>
        </>
    );
}

RemindersIndex.layout = {
    breadcrumbs: [{ title: 'Reminder', href: remindersIndex() }],
};
