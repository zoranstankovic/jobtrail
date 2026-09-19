<script setup lang="ts">
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import { useEnums } from '@/composables/useEnums';
import { computed } from 'vue';

const props = defineProps<{ status: string | null }>();

const { label } = useEnums();

const variant = computed<BadgeVariants['variant']>(() => {
    switch (props.status) {
        case 'applied':
        case 'interviewing':
            return 'secondary';
        case 'offer':
        case 'accepted':
            return 'default';
        case 'rejected':
            return 'destructive';
        default:
            return 'outline';
    }
});
</script>

<template>
    <Badge :variant="variant">
        {{
            status === null ? 'Not applied' : label('applicationStatus', status)
        }}
    </Badge>
</template>
