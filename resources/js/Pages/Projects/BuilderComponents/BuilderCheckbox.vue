<template>
    <div class="xxsLight">
        <div>
            {{ project['Checkbox'][component.component_id]?.data?.checked ? $t('Yes') : $t('No') }}
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
        required: true,
    },
})

</script>

<style scoped>

</style>