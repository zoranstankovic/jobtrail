<script setup lang="ts">
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/vue3';
import { Briefcase, Building2, Send } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();

const items = [
    { title: 'Job Postings', href: '/postings', icon: Briefcase },
    { title: 'Applications', href: '/applications', icon: Send },
    { title: 'Companies', href: '/companies', icon: Building2 },
];

const currentPath = computed(() => page.url.split('?')[0]);
</script>

<template>
    <Sidebar collapsible="icon">
        <SidebarHeader class="px-4 py-3">
            <span class="text-base font-semibold">JobTrail</span>
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
    </Sidebar>
</template>
