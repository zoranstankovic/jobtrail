<script setup lang="ts">
import {
    TagsInput,
    TagsInputInput,
    TagsInputItem,
    TagsInputItemDelete,
    TagsInputItemText,
} from '@/components/ui/tags-input';

defineProps<{ id: string; suggestions: string[] }>();

const model = defineModel<string[]>({ required: true });

// reka-ui's tags can hold objects too; ours are always plain strings.
function update(values: unknown[]): void {
    model.value = values.map(String);
}
</script>

<template>
    <div>
        <TagsInput
            :model-value="model"
            add-on-blur
            add-on-paste
            @update:model-value="update"
        >
            <TagsInputItem v-for="skill in model" :key="skill" :value="skill">
                <TagsInputItemText />
                <TagsInputItemDelete />
            </TagsInputItem>
            <TagsInputInput
                :id="id"
                list="skill-suggestions"
                maxlength="100"
                placeholder="Type a skill and press Enter"
            />
        </TagsInput>
        <datalist id="skill-suggestions">
            <option
                v-for="suggestion in suggestions"
                :key="suggestion"
                :value="suggestion"
            />
        </datalist>
    </div>
</template>
