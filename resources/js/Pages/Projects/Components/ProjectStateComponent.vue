<template>
    <div>
        <span class="rounded-full items-center font-medium px-3 py-1 my-2 text-sm ml-2 mb-1 inline-flex"
              :class="project.state?.color">
            {{ project.state?.name }}
        </span>
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
