<template>
    <div v-if="project.is_group && headerObject?.projectsOfGroup?.length > 0">
        <div class="xsDark">
            {{ $t('Projects in this group') }}:
        </div>
        <div v-if="headerObject?.projectsOfGroup?.length > 0" class="flex items-center gap-4 mt-3">
            <div v-for="(groupProject, index) in headerObject?.projectsOfGroup" class="group block shrink-0 bg-artwork-buttons-create/80 w-fit rounded-full border border-artwork-buttons-create/90 hover:bg-artwork-buttons-create duration-200 ease-in-out">
                <Link :disabled="checkPermission(groupProject)" :href="route('projects.tab', {project: groupProject?.id, projectTab: first_project_tab_id})" class="flex items-center text-white py-2 px-2">
                    <component :is="groupProject?.icon" class="size-5" :style="{color: groupProject?.color}" />
                    <div class="mx-2">
                        <p class="font-bold text-xs">{{ groupProject.name}}</p>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>

import {XIcon} from "@heroicons/vue/outline";
import Button from "@/Jetstream/Button.vue";
import {Link, usePage} from "@inertiajs/vue3";
import {usePermission} from "@/Composeables/Permission.js";
const { hasAdminRole, can } = usePermission(usePage().props);
const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    first_project_tab_id: {
        type: Number,
        required: true
    },
    headerObject: {
        type: Object,
        required: true
    }
})

const checkPermission = (project) => {
    const projectUsersIds = project?.users?.map(user => user.id);
    return !(hasAdminRole || projectUsersIds.includes(usePage().props.user.id));
}

</script>

<style scoped>

</style>