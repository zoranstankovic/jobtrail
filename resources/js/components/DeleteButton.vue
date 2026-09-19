<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import type { RouteDefinition } from '@/wayfinder';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

const props = withDefaults(
    defineProps<{
        action: RouteDefinition<'delete'>;
        title: string;
        description: string;
        label?: string;
    }>(),
    { label: 'Delete' },
);

function confirm(): void {
    router.visit(props.action, {
        preserveScroll: true,
        onError: (errors) => {
            // A refused delete (a domain rule in an Action) has no form field
            // to show its message under, so it becomes an error toast.
            const message = Object.values(errors)[0];

            if (message) {
                toast.error(message);
            }
        },
    });
}
</script>

<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <Button variant="destructive" size="sm">{{ label }}</Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription>{{
                    description
                }}</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="confirm">{{
                    label
                }}</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
