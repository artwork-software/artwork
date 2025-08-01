<template>
    <div class="my-2 flex items-start gap-x-4">
        <div>
            <div>
                <label class="xsDark font-bold" :class="inSidebar ? 'xsLight' : 'xsDark'">
                   {{ $t('Project Artist')}}
                </label>
            </div>
            <div class="mt-2 subpixel-antialiased xsDark">
                <span v-if="project.artist_name">{{ project.artist_name }}</span>
            </div>
        </div>
        <InfoButtonComponent :component="component" />
    </div>
</template>

<script setup>

import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";

const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    component: {
        type: Object,
        required: true
    },
    inSidebar: {
        type: Boolean,
        required: false,
        default: false
    }
})
</script>

<style scoped>

</style>