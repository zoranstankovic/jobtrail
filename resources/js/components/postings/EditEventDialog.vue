<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { fromDateTimeLocal, toDateTimeLocal } from '@/lib/dates';
import { update as updateEvent } from '@/routes/events';
import type { ApplicationEvent } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{ event: ApplicationEvent }>();

const open = ref(false);

const form = useForm({ occurred_at: '', note: '' });

// Load the event's current values every time the dialog opens.
watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.occurred_at = toDateTimeLocal(new Date(props.event.occurred_at));
        form.note = props.event.note ?? '';
    }
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        occurred_at: fromDateTimeLocal(data.occurred_at),
    })).submit(updateEvent(props.event.id), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="ghost" size="sm">Edit</Button>
        </DialogTrigger>
        <DialogContent>
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Edit event</DialogTitle>
                    <DialogDescription>
                        Correct the date or the note. The date must stay between
                        the events before and after this one.
                    </DialogDescription>
                </DialogHeader>
                <FieldGroup>
                    <FormField
                        :id="`event-${event.id}-occurred_at`"
                        label="When"
                        :error="form.errors.occurred_at"
                    >
                        <Input
                            :id="`event-${event.id}-occurred_at`"
                            v-model="form.occurred_at"
                            type="datetime-local"
                        />
                    </FormField>
                    <FormField
                        :id="`event-${event.id}-note`"
                        label="Note"
                        :error="form.errors.note"
                    >
                        <Textarea
                            :id="`event-${event.id}-note`"
                            v-model="form.note"
                        />
                    </FormField>
                </FieldGroup>
                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        Save
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
