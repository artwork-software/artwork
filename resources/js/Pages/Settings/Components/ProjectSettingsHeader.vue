<template>
    <app-layout :title="$t('Project Settings') + ' - ' + title">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconSettings"
                :title="title || $t('Project Settings')"
                icon-bg-class="bg-indigo-600/10 text-indigo-700"
                :description="description || $t('Define global settings for projects.')"
                :search-enabled="false"
            >
                <template #actions>
                    <slot name="actions"></slot>
                </template>
            </ToolbarHeader>

            <!-- Tabs outside the header card -->
            <BaseTabs :tabs="tabs" navigation-mode="links" />

            <div class="mt-6">
                <slot></slot>
            </div>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue';
import AppLayout from "@/Layouts/AppLayout.vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import BaseTabs from "@/Components/Tabs/BaseTabs.vue";
import {IconSettings} from '@tabler/icons-vue';

export default defineComponent({
    props: ['title', 'description'],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs
    },
    data() {
        return {
            IconSettings,
            tabs: [
                {
                    name: this.$t('Project Settings'),
                    href: route('project.settings'),
                    current: route().current('project.settings'),
                    hasPermission: true
                },
                {
                    name: this.$t('Project Role Settings'),
                    href: route('project-roles.index'),
                    current: route().current('project-roles.index'),
                    hasPermission: true
                },
                {
                    name: this.$t('Tab Settings'),
                    href: route('tab.index'),
                    current: route().current('tab.index'),
                    hasPermission: true
                },
                {
                    name: this.$t('Component Settings'),
                    href: route('component.index'),
                    current: route().current('component.index'),
                    hasPermission: true
                },
                {
                    name: this.$t('Print Layout Settings'),
                    href: route('project-print-layout.index'),
                    current: route().current('project-print-layout.index'),
                    hasPermission: true
                },
                {
                    name: this.$t('Project overview builder'),
                    href: route('project-management-builder.index'),
                    current: route().current('project-management-builder.index'),
                    hasPermission: true
                },
            ]
        }
    }
});
</script>

<style scoped>

</style>
