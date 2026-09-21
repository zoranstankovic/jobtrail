<script setup lang="ts">
import Pager from '@/components/Pager.vue';
import StatusBadge from '@/components/StatusBadge.vue';
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
import { daysSince, formatDate } from '@/lib/dates';
import { index as indexApplications } from '@/routes/applications';
import { show as showCompany } from '@/routes/companies';
import { index as indexPostings, show as showPosting } from '@/routes/postings';
import type {
    ApplicationListItem,
    ApplicationTab,
    Paginated,
} from '@/types/models';
import { Link } from '@inertiajs/vue3';

defineProps<{
    applications: Paginated<ApplicationListItem>;
    tab: string;
    tabs: ApplicationTab[];
}>();
</script>

<template>
    <AppLayout title="Applications">
        <div class="space-y-4">
            <nav
                class="bg-muted inline-flex flex-wrap rounded-lg p-1"
                aria-label="Application status"
            >
                <Link
                    v-for="item in tabs"
                    :key="item.key"
                    :href="indexApplications({ query: { tab: item.key } })"
                    class="rounded-md px-3 py-1.5 text-sm font-medium"
                    :class="
                        item.key === tab
                            ? 'bg-background shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    :aria-current="item.key === tab ? 'page' : undefined"
                >
                    {{ item.label }}
                    <span class="text-muted-foreground ml-1 tabular-nums">
                        {{ item.count }}
                    </span>
                </Link>
            </nav>

            <Empty
                v-if="applications.data.length === 0"
                class="border border-dashed"
            >
                <EmptyHeader>
                    <EmptyTitle>No applications here</EmptyTitle>
                    <EmptyDescription>
                        Save or apply to a job posting to track it here.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button as-child>
                        <Link :href="indexPostings()">Browse job postings</Link>
                    </Button>
                </EmptyContent>
            </Empty>

            <Table v-else>
                <TableHeader>
                    <TableRow>
                        <TableHead>Company</TableHead>
                        <TableHead>Job posting</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Applied</TableHead>
                        <TableHead>Last activity</TableHead>
                        <TableHead class="text-right">Days since</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="application in applications.data"
                        :key="application.id"
                    >
                        <TableCell>
                            <Link
                                :href="showCompany(application.company.id)"
                                class="underline-offset-4 hover:underline"
                            >
                                {{ application.company.name }}
                            </Link>
                        </TableCell>
                        <TableCell class="font-medium">
                            <Link
                                :href="showPosting(application.posting.id)"
                                class="underline-offset-4 hover:underline"
                            >
                                {{ application.posting.title }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <StatusBadge :status="application.status" />
                        </TableCell>
                        <TableCell>
                            {{ formatDate(application.applied_at) }}
                        </TableCell>
                        <TableCell>
                            {{ formatDate(application.last_activity_at) }}
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ daysSince(application.last_activity_at) }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pager :paginator="applications" />
        </div>
    </AppLayout>
</template>
