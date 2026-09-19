<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    create as createCompany,
    show as showCompany,
} from '@/routes/companies';
import type { CompanyListItem } from '@/types/models';
import { Link } from '@inertiajs/vue3';

defineProps<{ companies: CompanyListItem[] }>();
</script>

<template>
    <AppLayout title="Companies">
        <template #actions>
            <Button size="sm" as-child>
                <Link :href="createCompany()">New company</Link>
            </Button>
        </template>

        <Empty v-if="companies.length === 0" class="border border-dashed">
            <EmptyHeader>
                <EmptyTitle>No companies yet</EmptyTitle>
                <EmptyDescription>
                    Companies appear here once you add them.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <Button as-child>
                    <Link :href="createCompany()">Add a company</Link>
                </Button>
            </EmptyContent>
        </Empty>

        <Table v-else>
            <TableHeader>
                <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>City</TableHead>
                    <TableHead>Website</TableHead>
                    <TableHead class="text-right">Postings</TableHead>
                    <TableHead class="text-right">Applications</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="company in companies" :key="company.id">
                    <TableCell class="font-medium">
                        <Link
                            :href="showCompany(company.id)"
                            class="underline-offset-4 hover:underline"
                        >
                            {{ company.name }}
                        </Link>
                    </TableCell>
                    <TableCell>{{ company.city ?? '—' }}</TableCell>
                    <TableCell>
                        <a
                            v-if="company.website"
                            :href="company.website"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="underline-offset-4 hover:underline"
                        >
                            {{ company.website }}
                        </a>
                        <span v-else>—</span>
                    </TableCell>
                    <TableCell class="text-right">
                        {{ company.postings_count }}
                    </TableCell>
                    <TableCell class="text-right">
                        {{ company.applications_count }}
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </AppLayout>
</template>
