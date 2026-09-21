<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    endOfTodayLocal,
    fromDateTimeLocal,
    toDateTimeLocal,
} from '@/lib/dates';
import { store as storeApplication } from '@/routes/applications';
import { useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

const props = defineProps<{ postingId: number }>();

const form = useForm({ status: 'applied', occurred_at: toDateTimeLocal() });

function start(status: 'saved' | 'applied'): void {
    form.status = status;

    form.transform((data) => ({
        ...data,
        // "Save for later" is dated now by the server; only "Mark as
        // applied" sends the chosen date.
        occurred_at:
            status === 'applied' ? fromDateTimeLocal(data.occurred_at) : null,
    })).submit(storeApplication(props.postingId), {
        preserveScroll: true,
        onError: (errors) => {
            // The posting already has an application (e.g. another tab).
            if (errors.job_posting_id) {
                toast.error(errors.job_posting_id);
            }
        },
    });
}
</script>

<template>
    <div class="space-y-4">
        <p class="text-muted-foreground text-sm">You have not applied yet.</p>
        <Button
            variant="outline"
            class="w-full"
            :disabled="form.processing"
            @click="start('saved')"
        >
            Save for later
        </Button>
        <FormField
            id="occurred_at"
            label="Applied on"
            :error="form.errors.occurred_at"
        >
            <Input
                id="occurred_at"
                v-model="form.occurred_at"
                type="datetime-local"
                :max="endOfTodayLocal()"
            />
        </FormField>
        <Button
            class="w-full"
            :disabled="form.processing"
            @click="start('applied')"
        >
            Mark as applied
        </Button>
    </div>
</template>
