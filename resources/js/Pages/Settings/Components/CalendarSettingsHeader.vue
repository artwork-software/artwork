<template>
    <app-layout :title="$t('Calendar Settings') + ' - ' + title">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconCalendar"
                :title="title || $t('Calendar Settings')"
                icon-bg-class="bg-green-600/10 text-green-700"
                :description="description || $t('Define global settings for the calendar.')"
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
import {IconCalendar} from '@tabler/icons-vue';

export default defineComponent({
    props: ['title', 'description'],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs
    },
    data() {
        return {
            IconCalendar,
            tabs: [
                {
                    name: this.$t('Calendar Settings'),
                    href: route('calendar.settings'),
                    current: route().current('calendar.settings'),
                    permission: true
                },
            ]
        }
    }
});
</script>

<style scoped>

</style>
