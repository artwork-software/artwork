<template>
    <Link class="bg-gray-100 rounded-lg p-4 hover:bg-gray-200 transition-all duration-300 ease-in-out" :href="route('checklist_templates.edit', {checklist_template: checklist_template.id})">
        <div class="">
            <div class="flex items-center justify-center">
                <div class="headline3">
                    {{ checklist_template.name }}
                </div>
            </div>
            <div class="flex items-center justify-center text-xs mt-2">
                <p>{{ checklist_template.task_templates?.length ?? 0 }} {{ $t('Tasks') }}</p>
            </div>
        </div>
    </Link>
</template>

<script setup>

import { Link } from '@inertiajs/vue3';

const props = defineProps({
    checklist_template: {
        type: Object,
        required: true
    }
})

</script>

<style scoped>

</style>