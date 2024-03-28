<template>
    <div class="flex items-center">
        <span v-if="!project.is_group">
            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-4 w-4 mr-2" aria-hidden="true" alt=""/>
        </span>
        {{ $t('Belongs to') }}
        <Link :href="route('projects.show.info', {project: currentGroup.id})" class="text-buttonBlue ml-1">
            {{ currentGroup.name }}
        </Link>
    </div>
</template>

<script>
import {defineComponent} from "vue";
import {Link} from "@inertiajs/inertia-vue3";

export default defineComponent({
    props: [
        'project',
        'currentGroup'
    ],
    components: {
        Link
    }
});
</script>
