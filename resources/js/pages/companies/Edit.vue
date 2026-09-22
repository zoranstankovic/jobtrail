<script setup lang="ts">
import CompanyForm from '@/components/companies/CompanyForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { update as updateCompany } from '@/routes/companies';
import type { Company } from '@/types/models';

const props = defineProps<{ company: Company; atsSuggestions: string[] }>();

const initial = {
    name: props.company.name,
    website: props.company.website ?? '',
    city: props.company.city ?? '',
    notes: props.company.notes ?? '',
    careers_url: props.company.careers_url ?? '',
    ats: props.company.ats ?? '',
    ats_jobs_url: props.company.ats_jobs_url ?? '',
};
</script>

<template>
    <AppLayout :title="`Edit ${company.name}`">
        <CompanyForm
            :initial="initial"
            :action="updateCompany(company.id)"
            :ats-suggestions="atsSuggestions"
            submit-label="Save changes"
        />
    </AppLayout>
</template>
