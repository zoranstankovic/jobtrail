<script setup lang="ts">
import CompanyForm from '@/components/companies/CompanyForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { store as storeCompany } from '@/routes/companies';

defineProps<{ atsSuggestions: string[] }>();
</script>

<template>
    <AppLayout title="New company">
        <CompanyForm
            :initial="{
                name: '',
                website: '',
                city: '',
                notes: '',
                careers_url: '',
                ats: '',
                ats_jobs_url: '',
            }"
            :action="storeCompany()"
            :ats-suggestions="atsSuggestions"
            submit-label="Create company"
        />
    </AppLayout>
</template>
