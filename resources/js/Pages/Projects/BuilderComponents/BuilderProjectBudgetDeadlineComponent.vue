<template>
    <h3 class="headline3 hidden print:block print:mb-2">{{ $t('Budget deadline') }}</h3>
    <div class="flex items-center gap-x-1 xxsLight print:xsDark" v-if="project.budget_deadline">
        <component :is="IconClock" class="h-4 w-4" stroke-width="2"/>
        <span class="font-bold">{{ project.budget_deadline }}</span>
    </div>
</template>

<script setup>

import {IconClock} from "@tabler/icons-vue";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    }
})

</script>

<style scoped>

</style>