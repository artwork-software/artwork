<template>
    <div class="flex font-black font-lexend text-primary tracking-wide text-sm items-center">
        <span v-if="project?.is_group">
            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-4 w-4 mr-2" aria-hidden="true"/>
        </span>
        <h3 class=" group-hover/project:text-artwork-buttons-create duration-300 ease-in-out">{{ project.title }}</h3>
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