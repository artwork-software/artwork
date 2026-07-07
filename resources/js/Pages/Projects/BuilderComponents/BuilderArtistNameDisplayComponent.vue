<template>
    <div class="min-w-0">
        <div
            v-if="artistText"
            v-tooltip.top="{ value: artistText, appendTo: 'body', class: 'aw-tooltip' }"
            class="font-lexend font-black tracking-wide text-sm text-primary truncate"
        >
            {{ artistText }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    component: {
        type: Object,
        required: false,
    },
})

const artistText = computed(() => props.project?.artist_name || '')
</script>

<style scoped>
</style>
