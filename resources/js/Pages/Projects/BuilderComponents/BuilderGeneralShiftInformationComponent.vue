<template>
    <div class="xxsLight">
        <div class="line-clamp-3" v-if="project.shift_description">
            {{ project.shift_description }}
        </div>
        <div v-else>
            {{ $t('No shift information has been entered yet') }}
        </div>
    </div>
</template>

<script setup>

const props = defineProps({
    project: {
        type: Object,
        required: true,
    }
})

</script>

<style scoped>

</style>