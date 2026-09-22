<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        skills: string[];
        // The skill the list is filtered by: it is always shown, so the
        // reason a row matches stays visible.
        highlight?: string | null;
        max?: number;
    }>(),
    { highlight: null, max: 3 },
);

const ordered = computed(() => {
    const wanted = props.highlight?.toLowerCase();
    const index = props.skills.findIndex(
        (skill) => skill.toLowerCase() === wanted,
    );

    if (index < props.max) {
        return props.skills;
    }

    return [
        props.skills[index],
        ...props.skills.filter((_, position) => position !== index),
    ];
});

// A "+1" badge would take the room of the one skill it hides.
const shown = computed(() =>
    ordered.value.length <= props.max + 1
        ? ordered.value
        : ordered.value.slice(0, props.max),
);

const hidden = computed(() => ordered.value.slice(shown.value.length));
</script>

<template>
    <div class="flex flex-wrap gap-1">
        <Badge v-for="skill in shown" :key="skill" variant="secondary">
            {{ skill }}
        </Badge>
        <Tooltip v-if="hidden.length > 0">
            <TooltipTrigger as-child>
                <Badge
                    variant="outline"
                    tabindex="0"
                    :aria-label="`${hidden.length} more skills: ${hidden.join(', ')}`"
                >
                    +{{ hidden.length }}
                </Badge>
            </TooltipTrigger>
            <TooltipContent>{{ hidden.join(', ') }}</TooltipContent>
        </Tooltip>
    </div>
</template>
