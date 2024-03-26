<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import {Link} from "@inertiajs/inertia-vue3";

export default {
    name: "ProjectHeaderComponent",
    components: {
        Link,
        AppLayout
    },
    props: {
        headerObject: {
            type: Object,
            required: true
        }
    }
}
</script>

<template>
    <AppLayout>

        <!-- Project Header -->


        <!-- tabs -->
        <div class="w-full h-full">
            <div class="ml-14">
                <div class="hidden sm:block">
                    <div class="border-gray-200">
                        <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                            <Link v-for="tab in headerObject.tabs" :key="tab?.name"
                                  :href="route('project.tab.test', {project: headerObject.project.id, projectTab: tab.id})"
                                  :class="[tab.id === headerObject.currentTabId ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                                  :aria-current="tab.id === headerObject.currentTabId ? 'page' : undefined">
                                {{ tab.name }}
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="ml-14">
            <slot />
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
