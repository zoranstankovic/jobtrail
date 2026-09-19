<script setup lang="ts">
import DeleteButton from '@/components/DeleteButton.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    destroy as destroyCompany,
    edit as editCompany,
} from '@/routes/companies';
import { Link } from '@inertiajs/vue3';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Company, CompanyPosting } from '@/types/models';

defineProps<{ company: Company; postings: CompanyPosting[] }>();
</script>

<template>
    <AppLayout :title="company.name">
        <template #actions>
            <Button variant="outline" size="sm" as-child>
                <Link :href="editCompany(company.id)">Edit</Link>
            </Button>
            <DeleteButton
                :action="destroyCompany(company.id)"
                :title="`Delete ${company.name}?`"
                description="This cannot be undone. A company that still has job postings cannot be deleted."
            />
        </template>
        <div class="grid gap-8 lg:grid-cols-3">
            <dl class="grid content-start gap-4 text-sm">
                <div>
                    <dt class="text-muted-foreground">City</dt>
                    <dd>{{ company.city ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Website</dt>
                    <dd>
                        <a
                            v-if="company.website"
                            :href="company.website"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="break-all underline-offset-4 hover:underline"
                        >
                            {{ company.website }}
                        </a>
                        <span v-else>—</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Notes</dt>
                    <dd class="whitespace-pre-line">
                        {{ company.notes ?? '—' }}
                    </dd>
                </div>
            </dl>

            <section class="lg:col-span-2">
                <h2 class="mb-3 text-base font-medium">Job postings</h2>
                <p
                    v-if="postings.length === 0"
                    class="text-muted-foreground text-sm"
                >
                    No job postings for this company yet.
                </p>
                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Location</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="posting in postings" :key="posting.id">
                            <TableCell class="font-medium">
                                {{ posting.title }}
                            </TableCell>
                            <TableCell>{{ posting.location ?? '—' }}</TableCell>
                            <TableCell>
                                <StatusBadge :status="posting.status" />
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </section>
        </div>
    </AppLayout>
</template>
