<script>
import {Link} from "@inertiajs/vue3";

export default {
    name: "ProjectTabs",
    components: {Link},
    computed: {
        tabs() {
            return [
                {
                    name: this.$t('Project Settings'),
                    href: route('project.settings'),
                    current: route().current('project.settings'),
                    show: true
                },
                {
                    name: this.$t('Project Role Settings'),
                    href: route('project-roles.index'),
                    current: route().current('project-roles.index'),
                    show: true
                },
                {
                    name: this.$t('Tab Settings'),
                    href: route('tab.index'),
                    current: route().current('tab.index'),
                    show: true
                },
                {
                    name: this.$t('Component Settings'),
                    href: route('component.index'),
                    current: route().current('component.index'),
                    show: true
                },
                {
                    name: this.$t('Print Layout Settings'),
                    href: route('project-print-layout.index'),
                    current: route().current('project-print-layout.index'),
                    show: true
                },
                {
                    name: this.$t('Project overview builder'),
                    href: route('project-management-builder.index'),
                    current: route().current('project-management-builder.index'),
                    show: true
                },
            ]
        },
    },
}
</script>

<template>
    <div class="w-full">
        <div class="mb-3">
            <div class="hidden sm:block">
                <div class="border-gray-200">
                    <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                        <Link v-for="tab in tabs" :key="tab?.name"
                              :href="tab.href"
                              :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                              :aria-current="tab.current ? 'page' : undefined" v-show="tab.show">
                            {{ tab.name }}
                        </Link>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
