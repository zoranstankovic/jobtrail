<script setup lang="ts">
import PostingForm from '@/components/postings/PostingForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { store as storePosting } from '@/routes/postings';
import type { PostingFormData } from '@/types/models';

defineProps<{ companies: string[]; sources: string[] }>();

const initial: PostingFormData = {
    company: '',
    title: '',
    url: '',
    source: '',
    location: '',
    work_mode: '',
    employment_type: '',
    seniority: '',
    salary_min: '',
    salary_max: '',
    salary_currency: 'EUR',
    salary_period: '',
    posted_at: '',
    description: '',
};
</script>

<template>
    <AppLayout title="New job posting">
        <PostingForm
            :initial="initial"
            :action="storePosting()"
            submit-label="Create posting"
            :companies="companies"
            :sources="sources"
        />
    </AppLayout>
</template>
