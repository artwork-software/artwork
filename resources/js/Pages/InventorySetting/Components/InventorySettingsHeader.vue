<template>
    <app-layout :title="$t('Inventory Settings') + ' - ' + title">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconPackage"
                :title="title || $t('Inventory Settings')"
                icon-bg-class="bg-purple-600/10 text-purple-700"
                :description="description || $t('Define global settings for inventory planning.')"
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
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import {IconPackage} from '@tabler/icons-vue';

export default defineComponent({
    props: ['title', 'description'],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs
    },
    data() {
        return {
            IconPackage,
            tabs: [
                {
                    name: this.$t('Categories & Sub-Categories'),
                    href: route('inventory-management.settings.category'),
                    current: route().current('inventory-management.settings.category'),
                    permission: true
                },
                {
                    name: this.$t('Properties'),
                    href: route('inventory-management.settings.properties'),
                    current: route().current('inventory-management.settings.properties'),
                    permission: true
                },
                {
                    name: this.$t('Status Settings'),
                    href: route('inventory-management.settings.status'),
                    current: route().current('inventory-management.settings.status'),
                    permission: true
                },
                {
                    name: this.$t('Tags'),
                    href: route('settings.inventory-tags.index'),
                    current: route().current('settings.inventory-tags.index'),
                    permission: true
                },
            ]
        }
    }
});
</script>

<style scoped>

</style>
