<template>
    <app-layout :title="$t('Event properties')">
        <EventSettingHeader>
            <template #actions>
                <BaseUIButton variant="primary" hide-icon @click="showEventPropertyModal = true">
                    <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                    {{ $t('New Event Property') }}
                </BaseUIButton>
            </template>
            <SettingsGuideBanner
                storage-key="settings-guide.event.properties"
                title="How does this area work?"
                :paragraphs="[
                    'Event properties are freely definable characteristics you can tick on an event — several at once, for example With audience or Hybrid.',
                    'They appear as icons on the event in the calendar, can be used as filters there and are included in the Excel export.',
                ]"
            />
            <div class="my-10">
                <div class="mb-4">
                    <BasePageTitle title="Event properties"
                                      description="Manage the event properties here."/>
                </div>
                <ul role="list" class="flex flex-col gap-y-3">
                    <li v-for="(eventProperty) in event_properties"
                        :key="eventProperty.id"
                        class="flex flex-row justify-between">
                        <div class="flex flex-row items-center gap-4">
                            <PropertyIcon as="div" class="h-12 w-12 rounded-full border border-border p-2"
                                       width="16" height="16"
                                       :name="eventProperty.icon"
                                       stroke-width="1.5"/>
                            <p class="text-lg/[21px] font-semibold text-text">{{ eventProperty.name }}</p>
                        </div>
                        <div class="flex items-center">
                            <BaseMenu>
                                <MenuItem v-slot="{ active }">
                                    <a href="#"
                                       @click="eventPropertyToEdit = eventProperty; showEventPropertyModal = true;"
                                       :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <IconEdit
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                                            aria-hidden="true"/>
                                        {{ $t('Edit event property') }}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#"
                                       @click="eventPropertyToDelete = eventProperty; showDeleteEventPropertyModal = true;"
                                       :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <IconTrash
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                                            aria-hidden="true"/>
                                        {{ $t('Delete event property') }}
                                    </a>
                                </MenuItem>
                            </BaseMenu>
                        </div>
                    </li>
                </ul>
            </div>
            <!-- confirm delete modal -->
            <EventPropertyModal v-if="showEventPropertyModal"
                                :event-property-to-edit="eventPropertyToEdit"
                                @close-modal="eventPropertyToEdit = null; showEventPropertyModal = false;"/>
            <confirm-delete-modal
                v-if="showDeleteEventPropertyModal"
                :title="$t('Delete event property')"
                :description="$t('Are you sure you want to delete the event property {0}? This is not reversible.', [eventPropertyToDelete.name])"
                @closed="showDeleteEventPropertyModal = false"
                @delete="deleteEventProperty"/>
            <!-- -->
        </EventSettingHeader>
    </app-layout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import EventSettingHeader from "@/Pages/Settings/EventSettingComponents/EventSettingHeader.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem} from "@headlessui/vue";
import {ref} from "vue";
import EventPropertyModal from "@/Pages/Settings/EventProperties/EventPropertyModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";
import {provide} from "vue";
import IconSelector from "@/Components/Icon/IconSelector.vue";
import {IconCirclePlus, IconEdit, IconTrash} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";

const props = defineProps({
        event_properties: {
            type: Array,
            required: true,
            default: []
        }
    }),
    eventPropertyToEdit = ref(null),
    showEventPropertyModal = ref(false),
    eventPropertyToDelete = ref(null),
    showDeleteEventPropertyModal = ref(false),
    deleteEventProperty = () => {
        router.delete(
            route(
                'event_settings.event_properties.delete',
                {
                    id: eventPropertyToDelete.value.id
                }
            ),
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => {
                    showDeleteEventPropertyModal.value = false
                }
            }
        );
    };

provide('event_properties', props.event_properties);

</script>
