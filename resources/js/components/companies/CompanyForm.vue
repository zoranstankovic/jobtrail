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
    atsSuggestions: string[];
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
            <h2 class="pt-2 text-base font-medium">Careers</h2>
            <FormField
                id="careers_url"
                label="Careers page"
                :error="form.errors.careers_url"
            >
                <Input
                    id="careers_url"
                    v-model="form.careers_url"
                    type="url"
                    placeholder="https://"
                    :aria-invalid="form.errors.careers_url ? true : undefined"
                />
            </FormField>
            <FormField
                id="ats"
                label="Applicant tracking system"
                :error="form.errors.ats"
            >
                <Input
                    id="ats"
                    v-model="form.ats"
                    list="ats-suggestions"
                    autocomplete="off"
                    placeholder="e.g. personio"
                    :aria-invalid="form.errors.ats ? true : undefined"
                />
                <datalist id="ats-suggestions">
                    <option
                        v-for="ats in atsSuggestions"
                        :key="ats"
                        :value="ats"
                    />
                </datalist>
            </FormField>
            <FormField
                id="ats_jobs_url"
                label="Job board on the ATS"
                :error="form.errors.ats_jobs_url"
            >
                <Input
                    id="ats_jobs_url"
                    v-model="form.ats_jobs_url"
                    type="url"
                    placeholder="https://"
                    :aria-invalid="form.errors.ats_jobs_url ? true : undefined"
                />
            </FormField>
            <div>
                <Button type="submit" :disabled="form.processing">
                    {{ submitLabel }}
                </Button>
            </div>
        </FieldGroup>
    </form>
</template>
