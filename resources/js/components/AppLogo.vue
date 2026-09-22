<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { usePage } from '@inertiajs/vue3';

const name = usePage().props.name;
</script>

<template>
    <div
        class="bg-primary text-primary-foreground flex aspect-square size-8 items-center justify-center rounded-md"
    >
        <AppLogoIcon class="size-6" />
    </div>
    <span class="truncate text-base font-semibold">{{ name }}</span>
</template>
