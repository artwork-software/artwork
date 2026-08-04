<template>
    <BaseCard elevation="raised" class="w-full" :class="classes">
        <slot></slot>
    </BaseCard>
</template>

<script setup>
import BaseCard from '@/Artwork/Cards/BaseCard.vue'

const props = defineProps({
    classes: {
        type: String,
        default: '',
    },

})

</script>

<style scoped>

</style>
