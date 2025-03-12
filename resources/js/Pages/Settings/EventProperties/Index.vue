<template>
    <app-layout :title="$t('Event Properties')">
        <EventSettingHeader>
            <div class="my-10">
                <div class="mb-4 flex items-center justify-between gap-x-10 max-w-3xl">
                    <TinyPageHeadline title="Event Eigenschaften"
                                      description="Hier kannst du die Event Eigenschaften verwalten."/>
                    <div>
                        <AddButtonSmall @click="showEventPropertyModal = true;" text="Event Eigenschaft hinzufügen"/>
                    </div>
                </div>
                <ul role="list" class="flex flex-col gap-y-3 max-w-3xl">
                    <li v-for="(eventProperty) in event_properties"
                        :key="eventProperty.id"
                        class="flex flex-row justify-between">
                        <div class="flex flex-row items-center gap-4">
                            <component as="div" class="h-12 w-12 rounded-full border border-gray-300 p-2"
                                       width="16" height="16"
                                       :is="eventProperty.icon"
                                       stroke-width="1.5"/>
                            <p class="mDark">{{ eventProperty.name }}</p>
                        </div>
                        <div class="flex items-center">
                            <BaseMenu>
                                <MenuItem v-slot="{ active }">
                                    <a href="#"
                                       @click="eventPropertyToEdit = eventProperty; showEventPropertyModal = true;"
                                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <PencilAltIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        {{ $t('Event-Eigenschaft bearbeiten') }}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#"
                                       @click="eventPropertyToDelete = eventProperty; showDeleteEventPropertyModal = true;"
                                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        {{ $t('Event-Eigenschaft löschen') }}
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
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {PencilAltIcon, TrashIcon} from "@heroicons/vue/outline";
import {MenuItem} from "@headlessui/vue";
import {ref} from "vue";
import EventPropertyModal from "@/Pages/Settings/EventProperties/EventPropertyModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";
import {provide} from "vue";

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
