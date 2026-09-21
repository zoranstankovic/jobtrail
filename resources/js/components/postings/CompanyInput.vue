<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { computed } from 'vue';

const props = defineProps<{
    id: string;
    companies: string[];
    invalid?: boolean;
}>();

const model = defineModel<string>({ required: true });

// A name that matches no existing company (ignoring case) creates one.
const isNew = computed(() => {
    const name = model.value.trim().toLowerCase();

    return (
        name !== '' &&
        !props.companies.some((company) => company.toLowerCase() === name)
    );
});
</script>

<template>
    <div class="space-y-1">
        <Input
            :id="id"
            v-model="model"
            list="company-suggestions"
            autocomplete="off"
            :aria-invalid="invalid ? true : undefined"
        />
        <datalist id="company-suggestions">
            <option
                v-for="company in companies"
                :key="company"
                :value="company"
            />
        </datalist>
        <p v-if="isNew" class="text-muted-foreground text-sm">
            Company “{{ model.trim() }}” will be created.
        </p>
    </div>
</template>
