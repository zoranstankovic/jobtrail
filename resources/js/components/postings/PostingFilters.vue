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

const props = defineProps<{
    filters: PostingFilters;
    sources: string[];
    skills: string[];
}>();

const { options } = useEnums();

// Every value is a string; '' means "no filter" and stays out of the URL.
const state = reactive({
    search: props.filters.search ?? '',
    application: props.filters.application ?? '',
    work_mode: props.filters.work_mode ?? '',
    seniority: props.filters.seniority ?? '',
    source: props.filters.source ?? '',
    skill: props.filters.skill ?? '',
    sort: props.filters.sort,
});

function apply(): void {
    const query = Object.fromEntries(
        Object.entries(state).filter(
            ([key, value]) =>
                value !== '' && !(key === 'sort' && value === 'created'),
        ),
    );

    router.get(indexPostings.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

watchDebounced(() => state.search, apply, { debounce: 300 });
watch(
    () => [
        state.application,
        state.work_mode,
        state.seniority,
        state.source,
        state.skill,
        state.sort,
    ],
    apply,
);
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
        <NativeSelect v-model="state.work_mode" aria-label="Work mode">
            <NativeSelectOption value="">Any work mode</NativeSelectOption>
            <NativeSelectOption
                v-for="option in options('workMode')"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </NativeSelectOption>
        </NativeSelect>
        <NativeSelect v-model="state.seniority" aria-label="Seniority">
            <NativeSelectOption value="">Any seniority</NativeSelectOption>
            <NativeSelectOption
                v-for="option in options('seniority')"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </NativeSelectOption>
        </NativeSelect>
        <NativeSelect v-model="state.source" aria-label="Source">
            <NativeSelectOption value="">Any source</NativeSelectOption>
            <NativeSelectOption
                v-for="source in sources"
                :key="source"
                :value="source"
            >
                {{ source }}
            </NativeSelectOption>
        </NativeSelect>
        <NativeSelect v-model="state.skill" aria-label="Skill">
            <NativeSelectOption value="">Any skill</NativeSelectOption>
            <NativeSelectOption
                v-for="skill in skills"
                :key="skill"
                :value="skill"
            >
                {{ skill }}
            </NativeSelectOption>
        </NativeSelect>
        <NativeSelect v-model="state.sort" aria-label="Sort" class="ml-auto">
            <NativeSelectOption value="created">
                Newest first
            </NativeSelectOption>
            <NativeSelectOption value="posted">
                Recently posted
            </NativeSelectOption>
        </NativeSelect>
    </div>
</template>
