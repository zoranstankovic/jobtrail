<script setup lang="ts">
import PostingForm from '@/components/postings/PostingForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { update as updatePosting } from '@/routes/postings';
import type { Posting, PostingFormData } from '@/types/models';

const props = defineProps<{
    posting: Posting;
    companies: string[];
    sources: string[];
    skills: string[];
}>();

const initial: PostingFormData = {
    company: props.posting.company.name,
    title: props.posting.title,
    url: props.posting.url ?? '',
    source: props.posting.source,
    location: props.posting.location ?? '',
    work_mode: props.posting.work_mode ?? '',
    employment_type: props.posting.employment_type ?? '',
    seniority: props.posting.seniority ?? '',
    salary_min: props.posting.salary_min?.toString() ?? '',
    salary_max: props.posting.salary_max?.toString() ?? '',
    salary_currency: props.posting.salary_currency,
    salary_period: props.posting.salary_period ?? '',
    // The date input wants "YYYY-MM-DD"; posted_at is midnight UTC.
    posted_at: props.posting.posted_at?.slice(0, 10) ?? '',
    description: props.posting.description ?? '',
    skills: [...props.posting.skills],
    already_applied: false,
    applied_at: '',
};
</script>

<template>
    <AppLayout :title="`Edit ${posting.title}`">
        <PostingForm
            :initial="initial"
            :action="updatePosting(posting.id)"
            submit-label="Save changes"
            :companies="companies"
            :sources="sources"
            :skills="skills"
        />
    </AppLayout>
</template>
