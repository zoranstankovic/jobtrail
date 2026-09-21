<script setup lang="ts">
import DeleteButton from '@/components/DeleteButton.vue';
import ApplicationNotes from '@/components/postings/ApplicationNotes.vue';
import ApplicationTimeline from '@/components/postings/ApplicationTimeline.vue';
import ChangeStatusDialog from '@/components/postings/ChangeStatusDialog.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatDate } from '@/lib/dates';
import { destroy as destroyApplication } from '@/routes/applications';
import type { Application } from '@/types/models';

defineProps<{ application: Application }>();
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <StatusBadge :status="application.status" />
            <ChangeStatusDialog :application="application" />
        </div>
        <p class="text-muted-foreground text-sm">
            Applied on {{ formatDate(application.applied_at) }}
        </p>
        <ApplicationNotes :application="application" />
        <section>
            <h3 class="mb-3 text-sm font-medium">History</h3>
            <ApplicationTimeline :events="application.events" />
        </section>
        <DeleteButton
            :action="destroyApplication(application.id)"
            label="Delete application"
            title="Delete this application?"
            description="Its whole status history is deleted too. The posting stays and becomes “not applied” again."
        />
    </div>
</template>
