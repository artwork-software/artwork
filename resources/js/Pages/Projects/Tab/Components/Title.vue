<template>
    <div class="my-2 flex items-start gap-x-4">
        <h1 class="font-bold" :style="{fontSize: data.data.title_size + 'px'}"
            :class="inSidebar ? 'text-white' : ' text-gray-900' ">
            {{ data.data.title }}
        </h1>
        <InfoButtonComponent :component="component" />
    </div>
</template>

<script>
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";

export default {
    name: "Title",
    components: {InfoButtonComponent},
    props: ['data', 'inSidebar', 'component'],
}
</script>
