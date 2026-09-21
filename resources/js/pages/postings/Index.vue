<script setup lang="ts">
import Pager from '@/components/Pager.vue';
import PostingFilters from '@/components/postings/PostingFilters.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import {
    Empty,
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
import { useEnums } from '@/composables/useEnums';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as showCompany } from '@/routes/companies';
import type {
    Paginated,
    PostingFilters as PostingFiltersValue,
    PostingListItem,
} from '@/types/models';
import { Link } from '@inertiajs/vue3';

defineProps<{
    postings: Paginated<PostingListItem>;
    filters: PostingFiltersValue;
    sources: string[];
    skills: string[];
}>();

const { label } = useEnums();
</script>

<template>
    <AppLayout title="Job Postings">
        <div class="space-y-4">
            <PostingFilters
                :filters="filters"
                :sources="sources"
                :skills="skills"
            />

            <Empty
                v-if="postings.data.length === 0"
                class="border border-dashed"
            >
                <EmptyHeader>
                    <EmptyTitle>No job postings found</EmptyTitle>
                    <EmptyDescription>
                        Add a posting, or change the filters.
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>

            <Table v-else>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Company</TableHead>
                        <TableHead>Location</TableHead>
                        <TableHead>Work mode</TableHead>
                        <TableHead>Seniority</TableHead>
                        <TableHead>Skills</TableHead>
                        <TableHead>Source</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="posting in postings.data"
                        :key="posting.id"
                    >
                        <TableCell class="font-medium">
                            {{ posting.title }}
                        </TableCell>
                        <TableCell>
                            <Link
                                :href="showCompany(posting.company.id)"
                                class="underline-offset-4 hover:underline"
                            >
                                {{ posting.company.name }}
                            </Link>
                        </TableCell>
                        <TableCell>{{ posting.location ?? '—' }}</TableCell>
                        <TableCell>
                            {{ label('workMode', posting.work_mode) }}
                        </TableCell>
                        <TableCell>
                            {{ label('seniority', posting.seniority) }}
                        </TableCell>
                        <TableCell>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="skill in posting.skills"
                                    :key="skill"
                                    variant="secondary"
                                >
                                    {{ skill }}
                                </Badge>
                            </div>
                        </TableCell>
                        <TableCell>{{ posting.source }}</TableCell>
                        <TableCell>
                            <StatusBadge :status="posting.status" />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pager :paginator="postings" />
        </div>
    </AppLayout>
</template>
