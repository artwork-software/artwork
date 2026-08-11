<template>
    <AppLayout :title="$t('BI Tags')">
        <EventSettingHeader>
            <SettingsGuideBanner
                class="mb-6"
                storage-key="settings-guide.event.bi-tags"
                title="How does this area work?"
                :paragraphs="[
                    'BI tags group event types for the BI module. For each tag, the number of event days per project is counted and shown in the BI dashboard and in BI exports (one column per tag).',
                    'Create tags here and assign event types to them — a tag without assigned event types is not counted.',
                ]"
            />

            <BiEventTypeTagManager :event-types="event_types" standalone />
        </EventSettingHeader>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import EventSettingHeader from "@/Pages/Settings/EventSettingComponents/EventSettingHeader.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import BiEventTypeTagManager from "@/Pages/Settings/EventType/Components/BiEventTypeTagManager.vue";

defineProps({
    event_types: {
        type: Array,
        default: () => [],
    },
});
</script>
