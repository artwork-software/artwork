<template>
    <div class="text-xs/[18px] text-text-subtle">
        <div v-if="component.data?.label" class="text-sm font-bold text-text mb-0.5">{{ component.data.label }}</div>
        <a
            v-if="linkText && linkText.length > 0"
            :href="linkText"
            target="_blank"
            class="text-accent-600 hover:underline line-clamp-2 block"
        >
            {{ linkText }}
        </a>
    </div>
</template>
<script setup>
import { computed } from 'vue';
const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: true,
    },
});
const linkText = computed(() => {
    return props.project['Link']?.[props.component.component_id]?.data?.text || '';
});
</script>
<style scoped>
</style>
