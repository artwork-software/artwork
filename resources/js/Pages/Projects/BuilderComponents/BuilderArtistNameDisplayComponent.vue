<template>
    <div class="font-lexend font-black tracking-wide text-sm text-primary">
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
