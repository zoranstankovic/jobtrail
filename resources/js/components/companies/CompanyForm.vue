<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { CompanyFormData } from '@/types/models';
import type { RouteDefinition } from '@/wayfinder';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    initial: CompanyFormData;
    action: RouteDefinition<'post'> | RouteDefinition<'put'>;
    submitLabel: string;
}>();

const form = useForm({ ...props.initial });

function submit(): void {
    form.submit(props.action);
}
</script>

<template>
    <form class="max-w-xl" @submit.prevent="submit">
        <FieldGroup>
            <FormField id="name" label="Name" :error="form.errors.name">
                <Input
                    id="name"
                    v-model="form.name"
                    :aria-invalid="form.errors.name ? true : undefined"
                />
            </FormField>
            <FormField
                id="website"
                label="Website"
                :error="form.errors.website"
            >
                <Input
                    id="website"
                    v-model="form.website"
                    type="url"
                    placeholder="https://"
                    :aria-invalid="form.errors.website ? true : undefined"
                />
            </FormField>
            <FormField id="city" label="City" :error="form.errors.city">
                <Input id="city" v-model="form.city" />
            </FormField>
            <FormField id="notes" label="Notes" :error="form.errors.notes">
                <Textarea id="notes" v-model="form.notes" class="min-h-32" />
            </FormField>
            <div>
                <Button type="submit" :disabled="form.processing">
                    {{ submitLabel }}
                </Button>
            </div>
        </FieldGroup>
    </form>
</template>
