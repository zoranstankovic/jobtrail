<script setup lang="ts">
import CompanyForm from '@/components/companies/CompanyForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { update as updateCompany } from '@/routes/companies';
import type { Company } from '@/types/models';

const props = defineProps<{ company: Company }>();

const initial = {
    name: props.company.name,
    website: props.company.website ?? '',
    city: props.company.city ?? '',
    notes: props.company.notes ?? '',
};
</script>

<template>
    <AppLayout :title="`Edit ${company.name}`">
        <CompanyForm
            :initial="initial"
            :action="updateCompany(company.id)"
            submit-label="Save changes"
        />
    </AppLayout>
</template>
