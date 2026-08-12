import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as respondentsIndex } from '@/routes/respondents';

type Respondent = {
    id: number;
    token: string;
    name: string | null;
    class_group: string;
    email: string;
    status: string;
    status_label: string;
    behavior_status: string | null;
    behavior_label: string | null;
    device_type: string | null;
    sent_at: string | null;
    first_access_at: string | null;
    response_at: string | null;
};

type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    from: number | null;
    to: number | null;
};

type Option = { value: string; label: string };

type Props = {
    respondents: Paginator<Respondent>;
    filters: { class_group?: string; status?: string; search?: string };
    classGroups: string[];
    statuses: Option[];
    behaviorStatuses: Option[];
};

const behaviorVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    berisiko: 'destructive',
    waspada: 'default',
    netral: 'secondary',
    tidak_merespons: 'outline',
};

const ALL = 'all';

function fmt(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('id-ID', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

export default function RespondentsIndex({
    respondents,
    filters,
    classGroups,
    statuses,
    behaviorStatuses,
}: Props) {
    const applyFilter = (patch: Record<string, string | undefined>) => {
        const next = { ...filters, ...patch };
        const query = Object.fromEntries(
            Object.entries(next).filter(([, v]) => v && v !== ALL),
        );
        router.get(respondentsIndex().url, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const behaviorLabel = (value: string | null) =>
        behaviorStatuses.find((b) => b.value === value)?.label ?? value;

    return (
        <>
            <Head title="Data Responden" />
            <div className="flex flex-col gap-4 p-4">
                <Card>
                    <CardContent className="flex flex-wrap gap-3 p-4">
                        <div className="relative min-w-56 flex-1">
                            <Search className="absolute top-2.5 left-2.5 size-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama, email, token…"
                                className="pl-8"
                                defaultValue={filters.search ?? ''}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter')
                                        applyFilter({
                                            search: e.currentTarget.value,
                                        });
                                }}
                            />
                        </div>
                        <Select
                            value={filters.class_group ?? ALL}
                            onValueChange={(v) =>
                                applyFilter({ class_group: v })
                            }
                        >
                            <SelectTrigger className="w-44">
                                <SelectValue placeholder="Semua kelas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>Semua kelas</SelectItem>
                                {classGroups.map((c) => (
                                    <SelectItem key={c} value={c}>
                                        {c}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={filters.status ?? ALL}
                            onValueChange={(v) => applyFilter({ status: v })}
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Semua status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>
                                    Semua status
                                </SelectItem>
                                {statuses.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>
                                        {s.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </CardContent>
                </Card>

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
                                            Kelas
                                        </th>
                                        <th className="p-3 font-medium">
                                            Status
                                        </th>
                                        <th className="p-3 font-medium">
                                            Perilaku
                                        </th>
                                        <th className="p-3 font-medium">
                                            Perangkat
                                        </th>
                                        <th className="p-3 font-medium">
                                            Dikirim
                                        </th>
                                        <th className="p-3 font-medium">
                                            Klik
                                        </th>
                                        <th className="p-3 font-medium">
                                            Respon
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {respondents.data.map((r) => (
                                        <tr
                                            key={r.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="p-3">
                                                <div className="font-medium">
                                                    {r.name ?? r.email}
                                                </div>
                                                <div className="font-mono text-xs text-muted-foreground">
                                                    {r.token.slice(0, 8)}…
                                                </div>
                                            </td>
                                            <td className="p-3">
                                                {r.class_group}
                                            </td>
                                            <td className="p-3">
                                                <Badge variant="outline">
                                                    {r.status_label}
                                                </Badge>
                                            </td>
                                            <td className="p-3">
                                                {r.behavior_status ? (
                                                    <Badge
                                                        variant={
                                                            behaviorVariant[
                                                                r
                                                                    .behavior_status
                                                            ] ?? 'outline'
                                                        }
                                                    >
                                                        {behaviorLabel(
                                                            r.behavior_status,
                                                        )}
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                            <td className="p-3 capitalize">
                                                {r.device_type ?? '—'}
                                            </td>
                                            <td className="p-3 text-muted-foreground">
                                                {fmt(r.sent_at)}
                                            </td>
                                            <td className="p-3 text-muted-foreground">
                                                {fmt(r.first_access_at)}
                                            </td>
                                            <td className="p-3 text-muted-foreground">
                                                {fmt(r.response_at)}
                                            </td>
                                        </tr>
                                    ))}
                                    {respondents.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={8}
                                                className="p-8 text-center text-muted-foreground"
                                            >
                                                Belum ada data responden.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        Menampilkan {respondents.from ?? 0}–
                        {respondents.to ?? 0} dari {respondents.total}
                    </span>
                    <div className="flex flex-wrap gap-1">
                        {respondents.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url ?? '#'}
                                preserveScroll
                                preserveState
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
            </div>
        </>
    );
}

RespondentsIndex.layout = {
    breadcrumbs: [{ title: 'Data Responden', href: respondentsIndex() }],
};
