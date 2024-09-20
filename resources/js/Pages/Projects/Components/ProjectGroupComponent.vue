<template>
    <div class="flex items-center" v-if="headerObject?.project?.is_group || headerObject?.project?.groups?.length > 0">
        <span>
            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-4 w-4 mr-2" aria-hidden="true" alt=""/>
        </span>
        <div class="flex items-center" v-if="headerObject?.project?.is_group">
            {{ $t('Projects of this group') }}:
            <div v-for="project in headerObject?.projectsOfGroup">
                <Link :href="route('projects.tab', {project: project?.id, projectTab: first_project_tab_id})"
                      class="text-artwork-buttons-create ml-1 mr-3">
                    {{ project?.name }}
                </Link>
            </div>
        </div>
        <div class="flex items-center" v-else>
            {{$t('This Project is part of the group')}}:
            <div v-for="project in headerObject?.project?.groups">
                <Link :href="route('projects.tab', {project: project?.id, projectTab: first_project_tab_id})"
                      class="text-artwork-buttons-create ml-1">
                    {{ project?.name }}
                </Link>
            </div>
        </div>
    </div>
</template>

<script>
import {defineComponent} from "vue";
import {Link} from "@inertiajs/vue3";

export default defineComponent({
    props: [
        'headerObject',
        'loadedProjectInformation',
        'first_project_tab_id'
    ],
    components: {
        Link
    }
});
</script>
