<template>
    <div class="text-xs">
        <span v-if="artistText">{{ artistText }}</span>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    project: {
        type: Object,
        required: true
    }
})

const artistText = computed(() => props.project?.artistNames || props.project?.artists || props.project?.artist_name || '')
</script>

<style scoped>

</style>
