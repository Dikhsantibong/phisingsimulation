import { Head, Link, router } from '@inertiajs/react';
import { Check, MessageCircle } from 'lucide-react';
import Swal from 'sweetalert2';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { followedUp, index as remindersIndex, wa } from '@/routes/reminders';

type Reminder = {
    id: number;
    token: string;
    respondent: string;
    class_group: string;
    reminder_type: string;
    channel: string;
    attempt_number: number;
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

export default function RemindersIndex({ reminders }: Props) {
    const markDone = (id: number) => {
        router.put(
            followedUp.url({ reminder: id }),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Reminder" />
            <div className="flex flex-col gap-4 p-4">
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left text-muted-foreground">
                                    <tr>
                                        <th className="p-3 font-medium">
                                            Responden
                                        </th>
                                        <th className="p-3 font-medium">
                                            Jenis
                                        </th>
                                        <th className="p-3 font-medium">
                                            Kanal
                                        </th>
                                        <th className="p-3 font-medium">Ke-</th>
                                        <th className="p-3 font-medium">
                                            Dijadwalkan
                                        </th>
                                        <th className="p-3 font-medium">
                                            Terkirim
                                        </th>
                                        <th className="p-3 font-medium">
                                            Status Penanganan
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
                                                <div className="font-medium">
                                                    {r.respondent}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {r.class_group}
                                                </div>
                                            </td>
                                            <td className="p-3">
                                                {r.reminder_type}
                                            </td>
                                            <td className="p-3 capitalize">
                                                {r.channel}
                                            </td>
                                            <td className="p-3 tabular-nums">
                                                {r.attempt_number}
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
                                                        Selesai
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                            <td className="p-3 text-right">
                                                {!r.followed_up_at && (
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="default"
                                                            className="bg-emerald-600 hover:bg-emerald-700"
                                                            onClick={() => {
                                                                const message = r.reminder_type === 'Tidak Menyelesaikan Kuesioner' 
                                                                    ? 'Sistem akan mengirim link evaluasi Tally ke WhatsApp responden.' 
                                                                    : 'Sistem akan mengirim ulang peringatan keamanan palsu ke WhatsApp responden.';
                                                                const isDark = document.documentElement.classList.contains('dark');
                                                                
                                                                Swal.fire({
                                                                    title: 'Kirim WhatsApp?',
                                                                    text: message,
                                                                    icon: 'question',
                                                                    showCancelButton: true,
                                                                    background: isDark ? '#18181b' : '#ffffff',
                                                                    color: isDark ? '#f8fafc' : '#0f172a',
                                                                    confirmButtonColor: '#059669',
                                                                    cancelButtonColor: isDark ? '#334155' : '#64748b',
                                                                    confirmButtonText: 'Ya, Kirim',
                                                                    cancelButtonText: 'Batal'
                                                                }).then((res) => {
                                                                    if (res.isConfirmed) {
                                                                        router.post(wa.url({ reminder: r.id }), {}, { preserveScroll: true });
                                                                    }
                                                                });
                                                            }}
                                                        >
                                                            <MessageCircle className="size-4" />
                                                            {r.reminder_type === 'Tidak Menyelesaikan Kuesioner' ? 'Kirim Tally' : 'Kirim Simulasi'}
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => markDone(r.id)}
                                                        >
                                                            <Check className="size-4" />
                                                            Tandai Selesai
                                                        </Button>
                                                    </div>
                                                )}
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
