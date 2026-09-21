<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { index as indexPostings } from '@/routes/postings';
import type { PostingFilters } from '@/types/models';
import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { reactive } from 'vue';

const props = defineProps<{ filters: PostingFilters }>();

// Every value is a string; '' means "no filter" and stays out of the URL.
const state = reactive({
    search: props.filters.search ?? '',
});

function apply(): void {
    const query = Object.fromEntries(
        Object.entries(state).filter(([, value]) => value !== ''),
    );

    router.get(indexPostings.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

watchDebounced(() => state.search, apply, { debounce: 300 });
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <Input
            v-model="state.search"
            type="search"
            placeholder="Search title and description"
            aria-label="Search"
            class="w-72"
        />
    </div>
</template>
