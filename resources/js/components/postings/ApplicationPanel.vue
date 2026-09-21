<script setup lang="ts">
import ApplicationTimeline from '@/components/postings/ApplicationTimeline.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatDate } from '@/lib/dates';
import type { Application } from '@/types/models';

defineProps<{ application: Application }>();
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <StatusBadge :status="application.status" />
        </div>
        <p class="text-muted-foreground text-sm">
            Applied on {{ formatDate(application.applied_at) }}
        </p>
        <section>
            <h3 class="mb-3 text-sm font-medium">History</h3>
            <ApplicationTimeline :events="application.events" />
        </section>
    </div>
</template>
