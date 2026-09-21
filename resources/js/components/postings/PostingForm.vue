<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import CompanyInput from '@/components/postings/CompanyInput.vue';
import SkillsInput from '@/components/postings/SkillsInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useEnums } from '@/composables/useEnums';
import {
    endOfTodayLocal,
    fromDateTimeLocal,
    toDateTimeLocal,
} from '@/lib/dates';
import type { PostingFormData } from '@/types/models';
import type { RouteDefinition } from '@/wayfinder';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    initial: PostingFormData;
    action: RouteDefinition<'post'> | RouteDefinition<'put'>;
    submitLabel: string;
    companies: string[];
    sources: string[];
    skills: string[];
    withApplied?: boolean;
}>();

const { options } = useEnums();

const form = useForm({ ...props.initial });

// Laravel reports a bad skill under its index ("skills.2"), not "skills".
const skillsError = computed(
    () =>
        form.errors.skills ??
        Object.entries(form.errors).find(([key]) =>
            key.startsWith('skills.'),
        )?.[1],
);

function setAlreadyApplied(value: unknown): void {
    form.already_applied = value === true;

    if (form.already_applied && form.applied_at === '') {
        form.applied_at = toDateTimeLocal();
    }
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        applied_at: fromDateTimeLocal(data.applied_at),
    })).submit(props.action);
}
</script>

<template>
    <form class="max-w-3xl" @submit.prevent="submit">
        <FieldGroup>
            <FormField
                id="company"
                label="Company"
                :error="form.errors.company"
            >
                <CompanyInput
                    id="company"
                    v-model="form.company"
                    :companies="companies"
                    :invalid="Boolean(form.errors.company)"
                />
            </FormField>

            <FormField id="title" label="Title" :error="form.errors.title">
                <Input
                    id="title"
                    v-model="form.title"
                    :aria-invalid="form.errors.title ? true : undefined"
                />
            </FormField>

            <div class="grid gap-6 sm:grid-cols-2">
                <FormField id="url" label="URL" :error="form.errors.url">
                    <Input
                        id="url"
                        v-model="form.url"
                        type="url"
                        placeholder="https://"
                        :aria-invalid="form.errors.url ? true : undefined"
                    />
                </FormField>
                <FormField
                    id="source"
                    label="Source"
                    :error="form.errors.source"
                >
                    <Input
                        id="source"
                        v-model="form.source"
                        list="source-suggestions"
                        autocomplete="off"
                        :aria-invalid="form.errors.source ? true : undefined"
                    />
                    <datalist id="source-suggestions">
                        <option
                            v-for="source in sources"
                            :key="source"
                            :value="source"
                        />
                    </datalist>
                </FormField>
                <FormField
                    id="location"
                    label="Location"
                    :error="form.errors.location"
                >
                    <Input id="location" v-model="form.location" />
                </FormField>
                <FormField
                    id="posted_at"
                    label="Posted on"
                    :error="form.errors.posted_at"
                >
                    <Input
                        id="posted_at"
                        v-model="form.posted_at"
                        type="date"
                    />
                </FormField>
                <FormField
                    id="work_mode"
                    label="Work mode"
                    :error="form.errors.work_mode"
                >
                    <NativeSelect id="work_mode" v-model="form.work_mode">
                        <NativeSelectOption value="">—</NativeSelectOption>
                        <NativeSelectOption
                            v-for="option in options('workMode')"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </NativeSelectOption>
                    </NativeSelect>
                </FormField>
                <FormField
                    id="employment_type"
                    label="Employment type"
                    :error="form.errors.employment_type"
                >
                    <NativeSelect
                        id="employment_type"
                        v-model="form.employment_type"
                    >
                        <NativeSelectOption value="">—</NativeSelectOption>
                        <NativeSelectOption
                            v-for="option in options('employmentType')"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </NativeSelectOption>
                    </NativeSelect>
                </FormField>
                <FormField
                    id="seniority"
                    label="Seniority"
                    :error="form.errors.seniority"
                >
                    <NativeSelect id="seniority" v-model="form.seniority">
                        <NativeSelectOption value="">—</NativeSelectOption>
                        <NativeSelectOption
                            v-for="option in options('seniority')"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </NativeSelectOption>
                    </NativeSelect>
                </FormField>
            </div>

            <div class="grid gap-6 sm:grid-cols-4">
                <FormField
                    id="salary_min"
                    label="Salary from"
                    :error="form.errors.salary_min"
                >
                    <Input
                        id="salary_min"
                        v-model="form.salary_min"
                        inputmode="numeric"
                    />
                </FormField>
                <FormField
                    id="salary_max"
                    label="Salary to"
                    :error="form.errors.salary_max"
                >
                    <Input
                        id="salary_max"
                        v-model="form.salary_max"
                        inputmode="numeric"
                    />
                </FormField>
                <FormField
                    id="salary_currency"
                    label="Currency"
                    :error="form.errors.salary_currency"
                >
                    <Input
                        id="salary_currency"
                        v-model="form.salary_currency"
                        maxlength="3"
                    />
                </FormField>
                <FormField
                    id="salary_period"
                    label="Per"
                    :error="form.errors.salary_period"
                >
                    <NativeSelect
                        id="salary_period"
                        v-model="form.salary_period"
                    >
                        <NativeSelectOption value="">—</NativeSelectOption>
                        <NativeSelectOption
                            v-for="option in options('salaryPeriod')"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </NativeSelectOption>
                    </NativeSelect>
                </FormField>
            </div>

            <FormField id="skills" label="Skills" :error="skillsError">
                <SkillsInput
                    id="skills"
                    v-model="form.skills"
                    :suggestions="skills"
                />
            </FormField>

            <FormField
                id="description"
                label="Description"
                :error="form.errors.description"
            >
                <Textarea
                    id="description"
                    v-model="form.description"
                    class="min-h-64"
                    placeholder="Paste the full job ad here."
                />
            </FormField>

            <div v-if="withApplied" class="space-y-3 rounded-md border p-4">
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="already_applied"
                        :model-value="form.already_applied"
                        @update:model-value="setAlreadyApplied"
                    />
                    <Label for="already_applied">I already applied</Label>
                </div>
                <FormField
                    v-if="form.already_applied"
                    id="applied_at"
                    label="Applied on"
                    :error="form.errors.applied_at"
                >
                    <Input
                        id="applied_at"
                        v-model="form.applied_at"
                        type="datetime-local"
                        :max="endOfTodayLocal()"
                        class="w-fit"
                    />
                </FormField>
            </div>

            <div>
                <Button type="submit" :disabled="form.processing">
                    {{ submitLabel }}
                </Button>
            </div>
        </FieldGroup>
    </form>
</template>
