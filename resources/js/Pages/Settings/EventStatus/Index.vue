<template>
    <app-layout :title="$t('Event Settings')">

        <EventSettingHeader>

            <div class="flex items-center gap-x-2">
                <Switch v-model="settingsForm.enable_status" :class="[settingsForm.enable_status ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2']">
                    <span :class="[settingsForm.enable_status ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none relative inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                      <span :class="[settingsForm.enable_status ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                        <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                          <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </span>
                      <span :class="[settingsForm.enable_status ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                        <svg class="size-3 text-artwork-buttons-create" fill="currentColor" viewBox="0 0 12 12">
                          <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                        </svg>
                      </span>
                    </span>
                </Switch>
                <div>
                    <p class="xsDark">{{ $t('Would you like to use the ‘Event Status’ module in {0}?', [usePage().props.page_title])}}</p>
                </div>
            </div>


            <div class="my-10" v-if="enable_status">
                <div class="mb-4 flex items-center justify-between gap-x-10">
                    <TinyPageHeadline
                        title="Event Status"
                        description="Hier kannst du die Event Status verwalten."
                    />
                    <div>
                        <AddButtonSmall @click="showCreateEventStatusModal = true" text="Event Status hinzufügen" />
                    </div>
                </div>

                <div class="">
                    <div>
                        <VisualFeedback :show-save-success="showVisualFeedback" />
                    </div>
                    <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="eventStatuses" @start="dragging=true" @end="dragging=false" @change="reorderEventStatus(eventStatuses)">
                        <template #item="{element}" :key="element.id">
                            <div :key="element" class="flex justify-between gap-x-6 py-3" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                <div class="flex gap-x-4">
                                    <div class="min-w-0 flex-auto">
                                        <p class="text-sm font-semibold leading-6 text-gray-900 flex items-center gap-x-2">
                                            <span class="h-14 w-14 block rounded-full border" :style="{'backgroundColor' : element.color }"/>
                                            {{ element.name }}
                                            <span v-if="element.default" class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 ml-10">{{ $t('Default') }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <component :is="IconGripVertical" class="h-5 w-5" />
                                    <BaseMenu has-no-offset>
                                        <MenuItem @click="updateEventStatus(element)"
                                                  v-slot="{ active }">
                                            <a :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                                <IconEdit stroke-width="1.5"
                                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                                          aria-hidden="true"/>
                                                {{$t('Edit')}}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-if="!element.default" @click="openDeleteEventStatusModal(element)"
                                                  v-slot="{ active }">
                                            <a :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                                <IconTrash stroke-width="1.5"
                                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                                           aria-hidden="true"/>
                                                {{$t('Delete')}}
                                            </a>
                                        </MenuItem>
                                    </BaseMenu>
                                </div>
                            </div>

                        </template>
                    </draggable>
                </div>
            </div>

            <AddEditEventStatusModal
                v-if="showCreateEventStatusModal"
                @closeModal="closeAddEditStatusModal"
                :event-status-to-edit="eventStatusToEdit"
            />

            <ConfirmDeleteModal
                title="Event Status löschen"
                description="Möchtest du den Event Status wirklich löschen?"
                v-if="showDeleteEventStatusModal"
                @closed="showDeleteEventStatusModal = false"
                @delete="deleteEventStatus"
                />

        </EventSettingHeader>

    </app-layout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import EventSettingHeader from "@/Pages/Settings/EventSettingComponents/EventSettingHeader.vue";
import {MenuItem, Switch} from "@headlessui/vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import {ref, watch} from "vue";
import {IconEdit, IconGripVertical, IconTrash} from "@tabler/icons-vue";
import draggable from "vuedraggable";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import AddEditEventStatusModal from "@/Pages/Settings/EventStatus/Components/AddEditEventStatusModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import VisualFeedback from "@/Components/Feedback/VisualFeedback.vue";

const props = defineProps({
    eventStatuses: {
        type: Object,
        required: true
    },
    enable_status: {
        type: Boolean,
        required: true
    }
})

const dragging = ref(false)
const showCreateEventStatusModal = ref(false)
const eventStatusToEdit = ref(null);
const eventStatusToDelete = ref(null);
const showDeleteEventStatusModal = ref(false);
const showVisualFeedback = ref(false);

const settingsForm = useForm({
    enable_status: props.enable_status
})

const updateEventStatusSettings = () => {
    settingsForm.patch(route('event_status.update_settings'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success
        }
    })
}

const openDeleteEventStatusModal = (eventStatus) => {
    eventStatusToDelete.value = eventStatus
    showDeleteEventStatusModal.value = true
}

const deleteEventStatus = () => {
    router.delete(route('event_status.delete', {eventStatus: eventStatusToDelete.value.id}), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteEventStatusModal.value = false;
            eventStatusToDelete.value = null;
            showVisualFeedback.value = true;
        }
    })
}

const closeAddEditStatusModal = (bool) => {
    showCreateEventStatusModal.value = false
    eventStatusToEdit.value = null

    if(bool) {
        showVisualFeedback.value = true
    }
}

const reorderEventStatus = (eventStatuses) => {
    eventStatuses.map((status, index) => {
        status.order = index + 1
    })

    router.patch(route('event_status.reorder'), {
        eventStatuses: eventStatuses
    })
}

const updateEventStatus = (eventStatus) => {
    eventStatusToEdit.value = eventStatus
    showCreateEventStatusModal.value = true
}

watch(() => settingsForm.enable_status, () => {
    updateEventStatusSettings()
})

watch(() => showVisualFeedback.value, () => {
    if(showVisualFeedback.value) {
        setTimeout(() => {
            showVisualFeedback.value = false
        }, 3000)
    }
})

</script>

<style scoped>

</style>