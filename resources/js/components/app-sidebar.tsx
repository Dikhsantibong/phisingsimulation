import { Link } from '@inertiajs/react';
import {
    Download,
    LayoutGrid,
    Send,
    BellRing,
    Users,
    Megaphone,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as campaignsIndex } from '@/routes/campaigns';
import { index as exportIndex } from '@/routes/export';
import { index as remindersIndex } from '@/routes/reminders';
import { index as respondentsIndex } from '@/routes/respondents';
import { create as sendSimulation } from '@/routes/send-simulation';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Kampanye',
        href: campaignsIndex(),
        icon: Megaphone,
    },
    {
        title: 'Kirim Simulasi',
        href: sendSimulation(),
        icon: Send,
    },
    {
        title: 'Data Responden',
        href: respondentsIndex(),
        icon: Users,
    },
    {
        title: 'Reminder',
        href: remindersIndex(),
        icon: BellRing,
    },
    {
        title: 'Ekspor Data',
        href: exportIndex(),
        icon: Download,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
