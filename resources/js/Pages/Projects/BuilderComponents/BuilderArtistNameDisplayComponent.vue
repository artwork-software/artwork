<template>
    <div>
        <div v-if="artistText" class="font-lexend font-black tracking-wide text-sm text-primary truncate">
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
