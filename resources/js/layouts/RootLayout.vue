<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import { router } from '@inertiajs/vue3';
import { onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

// Registered as the persistent layout in app.ts: it is created once and stays
// mounted across visits, so a toast survives the page change of a redirect.
const stopListening = router.on('flash', (event) => {
    const flashed = event.detail.flash.toast;

    if (flashed === undefined) {
        return;
    }

    if (flashed.type === 'error') {
        toast.error(flashed.message);
    } else {
        toast.success(flashed.message);
    }
});

onUnmounted(stopListening);
</script>

<template>
    <slot />
    <!-- Bottom right: at the top, toasts covered the header's actions. -->
    <Toaster position="bottom-right" />
</template>
