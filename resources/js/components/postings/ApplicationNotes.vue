<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { update as updateApplication } from '@/routes/applications';
import type { Application } from '@/types/models';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ application: Application }>();

const form = useForm({ notes: props.application.notes ?? '' });

function submit(): void {
    form.submit(updateApplication(props.application.id), {
        preserveScroll: true,
        // The saved text becomes the new baseline, so isDirty turns false.
        onSuccess: () => form.defaults(),
    });
}
</script>

<template>
    <form class="space-y-2" @submit.prevent="submit">
        <FormField
            id="application_notes"
            label="Notes"
            :error="form.errors.notes"
        >
            <Textarea
                id="application_notes"
                v-model="form.notes"
                class="min-h-24"
                placeholder="Contacts, salary talks, next steps"
            />
        </FormField>
        <Button
            type="submit"
            variant="outline"
            size="sm"
            :disabled="form.processing || !form.isDirty"
        >
            Save notes
        </Button>
    </form>
</template>
