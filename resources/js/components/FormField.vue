<script setup lang="ts">
import { Field, FieldError, FieldLabel } from '@/components/ui/field';

defineProps<{ id: string; label: string; error?: string }>();
</script>

<template>
    <Field :data-invalid="error ? true : undefined">
        <FieldLabel :for="id">{{ label }}</FieldLabel>
        <slot />
        <!-- [] when valid: FieldError renders an empty alert for [undefined]. -->
        <FieldError :errors="error ? [error] : []" />
    </Field>
</template>
