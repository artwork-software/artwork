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
                    'Two tags carry a key-figure role: the tag for “Performances” (also the capacity basis of the occupancy rate) and the tag for “Event days”. As long as they have no event types assigned, these key figures stay empty — there is no fallback to all events.',
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
