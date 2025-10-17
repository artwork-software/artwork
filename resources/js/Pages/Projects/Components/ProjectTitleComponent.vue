<template>
    <div class="flex items-center">
        <span v-if="project?.is_group">
            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-6 w-6 mr-2" aria-hidden="true" alt=""/>
        </span>
        {{ project.name }}
    </div>
</template>
<script>
import {defineComponent} from "vue";

export default defineComponent({
    props: [
        'project'
    ]
});
</script>
