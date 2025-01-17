<template>
    <div class="xxsLight line-clamp-3">
        <div v-for="(attributes, index) in project.attributes">
            {{ attributes.map((person) => person.name).join(', ') }}
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