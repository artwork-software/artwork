<template>
    <!--
        Dedicated print-layout renderer for the "Project period" component.
        Kept separate from BuilderProjectPeriodComponent.vue (used in the project
        management view) so that print-only styling changes never leak elsewhere.
    -->
    <div>
        <div class="text-sm" v-if="project.project_period">
            <span class="font-bold">
                <!-- Eintägige Projekte: ein Datum statt "15.03. – 15.03." -->
                {{ project.project_period.first_event_date }}<template v-if="project.project_period.last_event_date && project.project_period.last_event_date !== project.project_period.first_event_date"> – {{ project.project_period.last_event_date }}</template>
            </span>
        </div>
        <div v-else class="text-sm text-text-subtle">
            {{ $t('No entries') }}
        </div>
    </div>
</template>
<script setup>
const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: false,
    },
})
</script>
