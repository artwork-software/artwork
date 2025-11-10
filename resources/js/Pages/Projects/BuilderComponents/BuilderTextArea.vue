<template>


    <div class="">
        <p class="line-clamp-2 text-xs print:line-clamp-none ">
            {{ project['TextArea']?.[component.component_id ?? component.id]?.data?.text }}
        </p>
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
