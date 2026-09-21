<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { useEnums } from '@/composables/useEnums';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCalendarDate, formatDate } from '@/lib/dates';
import { formatSalary } from '@/lib/salary';
import { show as showCompany } from '@/routes/companies';
import type { Posting } from '@/types/models';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ posting: Posting }>();

const { label } = useEnums();

const details = computed(() => [
    { term: 'Location', value: props.posting.location ?? '—' },
    { term: 'Work mode', value: label('workMode', props.posting.work_mode) },
    {
        term: 'Employment type',
        value: label('employmentType', props.posting.employment_type),
    },
    { term: 'Seniority', value: label('seniority', props.posting.seniority) },
    {
        term: 'Salary',
        value: formatSalary(
            props.posting.salary_min,
            props.posting.salary_max,
            props.posting.salary_currency,
            props.posting.salary_period === null
                ? null
                : label('salaryPeriod', props.posting.salary_period),
        ),
    },
    { term: 'Source', value: props.posting.source },
    { term: 'Posted on', value: formatCalendarDate(props.posting.posted_at) },
    { term: 'Added on', value: formatDate(props.posting.created_at) },
]);
</script>

<template>
    <AppLayout :title="posting.title">
        <div class="grid gap-8 lg:grid-cols-3">
            <article class="space-y-6 lg:col-span-2">
                <div class="space-y-1 text-sm">
                    <Link
                        :href="showCompany(posting.company.id)"
                        class="font-medium underline-offset-4 hover:underline"
                    >
                        {{ posting.company.name }}
                    </Link>
                    <p v-if="posting.url">
                        <a
                            :href="posting.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-muted-foreground break-all underline-offset-4 hover:underline"
                        >
                            {{ posting.url }}
                        </a>
                    </p>
                </div>

                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div v-for="detail in details" :key="detail.term">
                        <dt class="text-muted-foreground">{{ detail.term }}</dt>
                        <dd>{{ detail.value }}</dd>
                    </div>
                </dl>

                <div
                    v-if="posting.skills.length > 0"
                    class="flex flex-wrap gap-1"
                >
                    <Badge
                        v-for="skill in posting.skills"
                        :key="skill"
                        variant="secondary"
                    >
                        {{ skill }}
                    </Badge>
                </div>

                <section>
                    <h2 class="mb-2 text-base font-medium">Description</h2>
                    <p
                        v-if="posting.description"
                        class="text-sm whitespace-pre-line"
                    >
                        {{ posting.description }}
                    </p>
                    <p v-else class="text-muted-foreground text-sm">
                        No description.
                    </p>
                </section>
            </article>
        </div>
    </AppLayout>
</template>
