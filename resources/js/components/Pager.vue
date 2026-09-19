<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/models';
import { Link } from '@inertiajs/vue3';

defineProps<{ paginator: Paginated<unknown> }>();
</script>

<template>
    <nav
        v-if="paginator.last_page > 1"
        class="flex items-center justify-between gap-4 text-sm"
        aria-label="Pagination"
    >
        <span class="text-muted-foreground">
            {{ paginator.from }}–{{ paginator.to }} of {{ paginator.total }}
        </span>
        <div class="flex gap-2">
            <Button
                v-if="paginator.prev_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="paginator.prev_page_url">Previous</Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>
                Previous
            </Button>
            <Button
                v-if="paginator.next_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="paginator.next_page_url">Next</Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>Next</Button>
        </div>
    </nav>
</template>
