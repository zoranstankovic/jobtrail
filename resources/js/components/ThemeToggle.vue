<script setup lang="ts">
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type Appearance, useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from '@lucide/vue';
import { type Component, computed } from 'vue';

const { appearance, cycleAppearance } = useAppearance();

const OPTIONS: Record<Appearance, { label: string; icon: Component }> = {
    light: { label: 'Theme: Light', icon: Sun },
    dark: { label: 'Theme: Dark', icon: Moon },
    system: { label: 'Theme: System', icon: Monitor },
};

const current = computed(() => OPTIONS[appearance.value]);
</script>

<template>
    <!-- One button that cycles Light -> Dark -> System, so it still fits
         when the sidebar is collapsed to icons (the label is the tooltip). -->
    <SidebarMenu>
        <SidebarMenuItem>
            <SidebarMenuButton
                :tooltip="current.label"
                @click="cycleAppearance"
            >
                <component :is="current.icon" />
                <span>{{ current.label }}</span>
            </SidebarMenuButton>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
