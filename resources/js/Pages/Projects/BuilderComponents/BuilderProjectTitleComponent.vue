<template>
    <div class="flex font-black font-lexend text-primary tracking-wide text-sm items-center">
        <span v-if="project?.is_group" class="print:hidden">
            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="size-5 mr-2" aria-hidden="true"/>
        </span>
        <span v-if="project?.key_visual_path !== null" class="print:hidden">
            <img :src="'/storage/keyVisual/' + project?.key_visual_path"
                 :alt="$t('Current key visual')"
                 class="mx-auto size-8 rounded-full object-cover mr-2">
        </span>
        <div class=" group-hover/project:text-artwork-buttons-create duration-300 ease-in-out text-dark flex items-center gap-x-1">
            <component v-if="project.icon && project.is_group" :is="project.icon" :style="{color: project?.color}" aria-hidden="true"/>
            {{ project.title }}
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