<template>
    <AppLayout title="Projektübersicht">
        <div class="">
            <div class="max-w-screen my-10 flex flex-col ml-14 mr-14">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex items-center justify-end">
                        <div class="w-full flex items-center">
                            <p class="items-center flex mr-2 headline1">
                                {{ $t('Projects') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mx-14">
                <div class="my-5 flex items-center gap-x-4 overflow-x-auto w-full bg-gray-100 px-3 py-3 rounded-lg">
                    <div class="w-56" v-for="component in components">
                        {{ $t(component.name) }}
                    </div>

                    <div v-for="project in projects.data">

                    </div>

                </div>

                <pre>
            {{ projects.data }}
        </pre>
            </div>

        </div>


    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    components: {
        type: Object,
        required: true,
    },
    projects: {
        type: Object,
        required: true,
    }
})

</script>

<style scoped>

</style>