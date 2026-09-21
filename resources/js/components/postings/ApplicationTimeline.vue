<script setup lang="ts">
import { useEnums } from '@/composables/useEnums';
import { formatDateTime } from '@/lib/dates';
import type { ApplicationEvent } from '@/types/models';

defineProps<{ events: ApplicationEvent[] }>();

const { label } = useEnums();
</script>

<template>
    <ol class="space-y-4 border-l pl-4">
        <li v-for="event in events" :key="event.id" class="relative">
            <span
                class="bg-foreground absolute top-1.5 -left-[21px] size-2 rounded-full"
                aria-hidden="true"
            />
            <p class="text-sm font-medium">
                {{ label('applicationStatus', event.to_status) }}
                <span
                    v-if="event.from_status !== null"
                    class="text-muted-foreground font-normal"
                >
                    from {{ label('applicationStatus', event.from_status) }}
                </span>
            </p>
            <p class="text-muted-foreground text-xs">
                {{ formatDateTime(event.occurred_at) }}
            </p>
            <p v-if="event.note" class="mt-1 text-sm whitespace-pre-line">
                {{ event.note }}
            </p>
        </li>
    </ol>
</template>
