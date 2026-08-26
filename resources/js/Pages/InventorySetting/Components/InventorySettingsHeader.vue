<template>
    <app-layout :title="$t('Inventory Settings') + ' - ' + title">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconPackage"
                :title="title || $t('Inventory Settings')"
                icon-bg-class="bg-special-violet-surface text-special-violet"
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
import {can, is} from 'laravel-permission-to-vuejs';

export default defineComponent({
    props: ['title', 'description'],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs
    },
    data() {
        // Nutzer mit reinen Material-Set-Rechten sehen nur den Material-Sets-Tab,
        // die übrigen Tabs verlangen weiterhin die Inventar-Settings-Permission.
        const canInventorySettings = is('artwork admin') || can('inventory.settings');
        return {
            IconPackage,
            tabs: [
                {
                    name: this.$t('General'),
                    href: route('inventory-management.settings.general'),
                    current: route().current('inventory-management.settings.general'),
                    permission: canInventorySettings
                },
                {
                    name: this.$t('Categories & Sub-Categories'),
                    href: route('inventory-management.settings.category'),
                    current: route().current('inventory-management.settings.category'),
                    permission: canInventorySettings
                },
                {
                    name: this.$t('Properties'),
                    href: route('inventory-management.settings.properties'),
                    current: route().current('inventory-management.settings.properties'),
                    permission: canInventorySettings
                },
                {
                    name: this.$t('Status Settings'),
                    href: route('inventory-management.settings.status'),
                    current: route().current('inventory-management.settings.status'),
                    permission: canInventorySettings
                },
                {
                    name: this.$t('Tags'),
                    href: route('settings.inventory-tags.index'),
                    current: route().current('settings.inventory-tags.index'),
                    permission: canInventorySettings
                },
                {
                    name: this.$t('Material Sets'),
                    href: route('material-sets.index'),
                    current: route().current('material-sets.index'),
                    permission: canInventorySettings || can('set.create_edit | set.delete')
                },
            ]
        }
    }
});
</script>

<style scoped>

</style>
