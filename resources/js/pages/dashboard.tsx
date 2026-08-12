import { Head } from '@inertiajs/react';
import {
    MousePointerClick,
    ShieldCheck,
    Users,
    ClipboardList,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

type Breakdown = { key: string; label: string; value: number };

type Props = {
    stats: {
        total: number;
        clicked: number;
        behaviorCompleted: number;
        questionnaireCompleted: number;
    };
    statusBreakdown: Breakdown[];
    behaviorBreakdown: Breakdown[];
};

const behaviorColors: Record<string, string> = {
    berisiko: 'bg-red-500',
    waspada: 'bg-emerald-500',
    netral: 'bg-amber-500',
    tidak_merespons: 'bg-neutral-400',
};

function BreakdownBars({
    title,
    data,
    colorFor,
}: {
    title: string;
    data: Breakdown[];
    colorFor?: (key: string) => string;
}) {
    const max = Math.max(1, ...data.map((d) => d.value));

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {data.map((item) => (
                    <div key={item.key} className="space-y-1">
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">
                                {item.label}
                            </span>
                            <span className="font-medium tabular-nums">
                                {item.value}
                            </span>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                className={`h-full rounded-full ${colorFor?.(item.key) ?? 'bg-blue-500'}`}
                                style={{
                                    width: `${(item.value / max) * 100}%`,
                                }}
                            />
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}

function StatCard({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: number;
    icon: typeof Users;
}) {
    return (
        <Card>
            <CardContent className="flex items-center gap-4 p-6">
                <div className="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <Icon className="size-5" />
                </div>
                <div>
                    <p className="text-2xl font-semibold tabular-nums">
                        {value}
                    </p>
                    <p className="text-sm text-muted-foreground">{label}</p>
                </div>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({
    stats,
    statusBreakdown,
    behaviorBreakdown,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label="Total Responden"
                        value={stats.total}
                        icon={Users}
                    />
                    <StatCard
                        label="Mengklik Tautan"
                        value={stats.clicked}
                        icon={MousePointerClick}
                    />
                    <StatCard
                        label="Simulasi Selesai"
                        value={stats.behaviorCompleted}
                        icon={ShieldCheck}
                    />
                    <StatCard
                        label="Kuesioner Selesai"
                        value={stats.questionnaireCompleted}
                        icon={ClipboardList}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <BreakdownBars
                        title="Distribusi Status Responden"
                        data={statusBreakdown}
                    />
                    <BreakdownBars
                        title="Distribusi Perilaku (Behavioral)"
                        data={behaviorBreakdown}
                        colorFor={(key) => behaviorColors[key] ?? 'bg-blue-500'}
                    />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
