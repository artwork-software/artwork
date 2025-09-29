<template>
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconCalendarEvent"
                :title="title || $t('Event Settings')"
                icon-bg-class="bg-orange-600/10 text-orange-700"
                :description="description || $t('Set global event settings.')"
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
</template>

<script>
import {defineComponent} from 'vue';
import AppLayout from "@/Layouts/AppLayout.vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import {IconCalendarEvent, IconPlus} from '@tabler/icons-vue';

export default defineComponent({
    props: ['title', 'description'],
    emits: ['add-event-type', 'add-event-status', 'add-event-property'],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs
    },
    data() {
        return {
            IconCalendarEvent,
            IconPlus,
            tabs: [
                {
                    name: this.$t('Event Types'),
                    href: route('event_types.management'),
                    current: route().current('event_types.management'),
                    permission: true
                },
                {
                    name: this.$t('Public holidays & school holidays'),
                    href: route('holiday.management'),
                    current: route().current('holiday.management'),
                    permission: true
                },
                {
                    name: this.$t('Event Status'),
                    href: route('event_status.management'),
                    current: route().current('event_status.management'),
                    permission: true
                },
                {
                    name: this.$t('Event Eigenschaften'),
                    href: route('event_settings.event_properties.index'),
                    current: route().current('event_settings.event_properties.index'),
                    permission: true
                }
            ]
        }
    }
});
</script>

<style scoped>

</style>
