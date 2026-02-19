<template>
    <div class="xxsLight max-w-44">
        <a
            v-if="linkText && linkText.length > 0"
            :href="linkText"
            target="_blank"
            class="text-blue-600 hover:underline line-clamp-2 block"
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
    return props.project['Link']?.[props.component.id]?.data?.text || '';
});
</script>

<style scoped>
</style>
