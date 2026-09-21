<script setup lang="ts">
import { Input } from '@/components/ui/input';
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
import { useEnums } from '@/composables/useEnums';
import { index as indexPostings } from '@/routes/postings';
import type { PostingFilters } from '@/types/models';
import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { reactive, watch } from 'vue';

const props = defineProps<{ filters: PostingFilters }>();

const { options } = useEnums();

// Every value is a string; '' means "no filter" and stays out of the URL.
const state = reactive({
    search: props.filters.search ?? '',
    application: props.filters.application ?? '',
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
watch(() => [state.application], apply);
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
        <NativeSelect v-model="state.application" aria-label="Application">
            <NativeSelectOption value="">All postings</NativeSelectOption>
            <NativeSelectOption value="none">Not applied</NativeSelectOption>
            <NativeSelectOption value="any">Any application</NativeSelectOption>
            <NativeSelectOption
                v-for="option in options('applicationStatus')"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </NativeSelectOption>
        </NativeSelect>
    </div>
</template>
