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
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useEnums } from '@/composables/useEnums';
import { fromDateTimeLocal, toDateTimeLocal } from '@/lib/dates';
import { store as storeEvent } from '@/routes/events';
import type { Application } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{ application: Application }>();

const { options } = useEnums();

const open = ref(false);

const form = useForm({ status: '', occurred_at: '', note: '' });

// The server rejects the current status (design §5.2), so it is not offered.
const statuses = computed(() =>
    options('applicationStatus').filter(
        (option) => option.value !== props.application.status,
    ),
);

// Every time the dialog opens, start from an empty form dated now.
watch(open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.clearErrors();
        form.occurred_at = toDateTimeLocal();
    }
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        occurred_at: fromDateTimeLocal(data.occurred_at),
    })).submit(storeEvent(props.application.id), {
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
            <Button variant="outline" size="sm">Change status</Button>
        </DialogTrigger>
        <DialogContent>
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Change status</DialogTitle>
                    <DialogDescription>
                        Record what happened and when. The date may lie in the
                        past, but not before the latest event.
                    </DialogDescription>
                </DialogHeader>
                <FieldGroup>
                    <FormField
                        id="change_status"
                        label="New status"
                        :error="form.errors.status"
                    >
                        <NativeSelect id="change_status" v-model="form.status">
                            <NativeSelectOption value="" disabled>
                                Choose a status
                            </NativeSelectOption>
                            <NativeSelectOption
                                v-for="option in statuses"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </NativeSelectOption>
                        </NativeSelect>
                    </FormField>
                    <FormField
                        id="change_occurred_at"
                        label="When"
                        :error="form.errors.occurred_at"
                    >
                        <Input
                            id="change_occurred_at"
                            v-model="form.occurred_at"
                            type="datetime-local"
                        />
                    </FormField>
                    <FormField
                        id="change_note"
                        label="Note (optional)"
                        :error="form.errors.note"
                    >
                        <Textarea id="change_note" v-model="form.note" />
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
