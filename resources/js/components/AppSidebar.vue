<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as indexApplications } from '@/routes/applications';
import { index as indexCompanies } from '@/routes/companies';
import { index as indexPostings } from '@/routes/postings';
import { Link, usePage } from '@inertiajs/vue3';
import { Briefcase, Building2, Send } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();

const items = [
    { title: 'Job Postings', href: indexPostings.url(), icon: Briefcase },
    { title: 'Applications', href: indexApplications.url(), icon: Send },
    { title: 'Companies', href: indexCompanies.url(), icon: Building2 },
];

const currentPath = computed(() => page.url.split('?')[0]);
</script>

<template>
    <Sidebar collapsible="icon">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        :tooltip="$page.props.name"
                        as-child
                    >
                        <Link :href="indexPostings.url()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel>Navigation</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in items" :key="item.href">
                        <SidebarMenuButton
                            as-child
                            :is-active="currentPath.startsWith(item.href)"
                            :tooltip="item.title"
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>
        <SidebarFooter>
            <ThemeToggle />
        </SidebarFooter>
    </Sidebar>
</template>
