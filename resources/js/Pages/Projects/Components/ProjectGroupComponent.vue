<template>
    <div class="flex items-center" v-if="project?.is_group ?? headerObject.project?.is_group">
        <span v-if="!project?.is_group ?? !headerObject.project?.is_group">
            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-4 w-4 mr-2" aria-hidden="true" alt=""/>
        </span>
        {{ $t('Belongs to') }}
        <Link :href="route('projects.tab', {project: currentGroup?.id ?? headerObject.currentGroup?.id, projectTab: this.first_project_tab_id})" class="text-buttonBlue ml-1">
            {{ currentGroup?.name ?? headerObject.currentGroup?.name }}
        </Link>
    </div>
</template>

<script>
import {defineComponent} from "vue";
import {Link} from "@inertiajs/inertia-vue3";

export default defineComponent({
    props: [
        'project',
        'currentGroup',
        'headerObject',
        'loadedProjectInformation',
        'first_project_tab_id'
    ],
    components: {
        Link
    }
});
</script>
