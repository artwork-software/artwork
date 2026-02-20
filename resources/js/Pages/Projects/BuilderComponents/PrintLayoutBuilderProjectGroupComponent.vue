<template>
    <div class="flex items-center" v-if="project?.is_group || project?.groupProjects?.length > 0">
        <span>
            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-4 w-4 mr-2" aria-hidden="true" alt=""/>
        </span>
        <div class="flex items-center" v-if="project?.is_group">
            {{ $t('Projects of this group') }}:
            <div v-for="project in project?.projectsOfGroup">
                <div class="text-artwork-buttons-create ml-1 mr-3">
                    {{ project?.name }}
                </div>
            </div>
        </div>
        <div class="flex items-center" v-else>
            {{$t('This Project is part of the group')}}:
            <div v-for="project in project?.groupProjects">
                <div class="text-artwork-buttons-create ml-1">
                    {{ project?.name }}
                </div>
            </div>
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

<style scoped>

</style>
