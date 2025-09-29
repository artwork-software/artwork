<template>
    <app-layout :title="$t('Rooms & areas') + (title ? ' - ' + title : '')">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconBuilding"
                :title="title || $t('Rooms & areas')"
                icon-bg-class="bg-teal-600/10 text-teal-700"
                :description="description || $t('Create areas and rooms and assign side rooms to individual rooms. Also define global properties for rooms.')"
                :search-enabled="false"
            >
                <template #actions>
                    <slot name="actions"></slot>
                </template>
            </ToolbarHeader>

            <!-- Tabs outside the header card -->
            <BaseTabs :tabs="tabs" />

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
import {IconBuilding} from '@tabler/icons-vue';

export default defineComponent({
    props: ['title', 'description'],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs
    },
    data() {
        return {
            IconBuilding,
            tabs: [
                {
                    name: this.$t('Rooms & areas'),
                    href: route('areas.management'),
                    current: route().current('areas.management'),
                    permission: true
                },
                {
                    name: this.$t('Sort rooms'),
                    href: route('rooms.move'),
                    current: route().current('rooms.move'),
                    permission: true
                }
            ]
        }
    }
});
</script>

<style scoped>

</style>
