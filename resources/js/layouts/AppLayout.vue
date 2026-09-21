<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{ title: string }>();

const page = usePage();

// Shared from HandleInertiaRequests, so the sidebar keeps its open/closed
// state across full page loads.
const sidebarOpen = computed(() => page.props.sidebarOpen !== false);
</script>

<template>
    <Head :title="title" />

    <SidebarProvider :default-open="sidebarOpen">
        <AppSidebar />
        <!-- min-w-0: a flex item is at least as wide as its content by
             default, so a wide table would widen the whole page. -->
        <SidebarInset class="min-w-0">
            <header class="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                <SidebarTrigger />
                <h1 class="text-sm font-medium">{{ title }}</h1>
                <div class="ml-auto flex items-center gap-2">
                    <slot name="actions" />
                </div>
            </header>
            <main class="flex-1 p-6">
                <slot />
            </main>
        </SidebarInset>
    </SidebarProvider>
</template>
