<script setup lang="ts">
import CompanyForm from '@/components/companies/CompanyForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { store as storeCompany } from '@/routes/companies';
</script>

<template>
    <AppLayout title="New company">
        <CompanyForm
            :initial="{ name: '', website: '', city: '', notes: '' }"
            :action="storeCompany()"
            submit-label="Create company"
        />
    </AppLayout>
</template>
