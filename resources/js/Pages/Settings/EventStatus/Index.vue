<template>
    <app-layout :title="$t('Event Settings')">

        <EventSettingHeader>
            <template #actions>
                <BaseUIButton variant="primary" hide-icon @click="showCreateEventStatusModal = true">
                    <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                    {{ $t('Add Event Status') }}
                </BaseUIButton>
            </template>

            <SettingsGuideBanner
                class="mb-6"
                storage-key="settings-guide.event.status"
                title="How does this area work?"
                :paragraphs="[
                    'Event statuses give every event a workflow state with its own colour — for example requested, confirmed or cancelled.',
                    'Use the master switch to enable the module for your instance, then create statuses and drag them into the order you want.',
                ]"
            />

            <div class="flex items-center gap-x-2">
                <Switch v-model="settingsForm.enable_status" :class="[settingsForm.enable_status ? 'bg-accent-600' : 'bg-border-subtle', 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent-600 focus:ring-offset-2']">
                    <span :class="[settingsForm.enable_status ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none relative inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                      <span :class="[settingsForm.enable_status ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                        <svg class="size-3 text-text-subtle" fill="none" viewBox="0 0 12 12">
                          <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </span>
                      <span :class="[settingsForm.enable_status ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                        <svg class="size-3 text-accent-600" fill="currentColor" viewBox="0 0 12 12">
                          <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                        </svg>
                      </span>
                    </span>
                </Switch>
                <div>
                    <p class="text-sm/5 font-semibold text-text">{{ $t('Would you like to use the ‘Event Status’ module in {0}?', [usePage().props.page_title])}}</p>
                </div>
            </div>

            <SettingsGuideBanner
                class="mt-4"
                variant="static"
                title="Where the status appears"
                :paragraphs="[
                    'When the module is active, the status appears in the event creation form, in bulk editing, in the calendar display settings and in the project print view.',
                    'The order of the list below determines the order in the status dropdown.',
                ]"
            />

            <div class="my-10" v-if="enable_status">
                <div class="mb-4">
                    <BasePageTitle
                        title="Event Status"
                        description="Manage the event statuses here."
                    />
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
                                        <p class="text-sm font-semibold leading-6 text-text flex items-center gap-x-2">
                                            <span class="h-14 w-14 block rounded-full border" :style="{'backgroundColor' : element.color }"/>
                                            {{ element.name }}
                                            <span v-if="element.default" class="inline-flex items-center rounded-md bg-surface-sunken px-2 py-1 text-xs font-medium text-text-muted ring-1 ring-inset border-border-subtle ml-10">{{ $t('Default') }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <component :is="IconGripVertical" class="h-5 w-5" />
                                    <BaseMenu has-no-offset>
                                        <MenuItem @click="updateEventStatus(element)"
                                                  v-slot="{ active }">
                                            <a :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                                <IconEdit stroke-width="1.5"
                                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                                                          aria-hidden="true"/>
                                                {{$t('Edit')}}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-if="!element.default" @click="openDeleteEventStatusModal(element)"
                                                  v-slot="{ active }">
                                            <a :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                                <IconTrash stroke-width="1.5"
                                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
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

            <div class="mt-10 border-t border-border-subtle pt-8">
                <div class="mb-4">
                    <BasePageTitle
                        title="Admission"
                        description="Optional admission time for events."
                    />
                </div>
                <div class="flex items-center gap-x-2">
                    <Switch v-model="settingsForm.enable_admission" :class="[settingsForm.enable_admission ? 'bg-accent-600' : 'bg-border-subtle', 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent-600 focus:ring-offset-2']">
                        <span :class="[settingsForm.enable_admission ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none relative inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                          <span :class="[settingsForm.enable_admission ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                            <svg class="size-3 text-text-subtle" fill="none" viewBox="0 0 12 12">
                              <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                          </span>
                          <span :class="[settingsForm.enable_admission ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                            <svg class="size-3 text-accent-600" fill="currentColor" viewBox="0 0 12 12">
                              <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414z" />
                            </svg>
                          </span>
                        </span>
                    </Switch>
                    <div>
                        <p class="text-sm/5 font-semibold text-text">{{ $t('Would you like to use the ‘Admission’ field for events in {0}?', [usePage().props.page_title])}}</p>
                    </div>
                </div>

                <SettingsGuideBanner
                    class="mt-4"
                    variant="static"
                    title="Where the admission time appears"
                    :paragraphs="[
                        'When the field is active, an optional admission time can be entered in the event creation form and in bulk editing. It refers to the start day of the event.',
                        'The admission time is shown on event tiles in the calendar and the shift plan — users can hide it via the calendar display settings.',
                    ]"
                />
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
import {IconEdit, IconGripVertical, IconCirclePlus, IconTrash} from "@tabler/icons-vue";
import draggable from "vuedraggable";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import AddEditEventStatusModal from "@/Pages/Settings/EventStatus/Components/AddEditEventStatusModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import VisualFeedback from "@/Components/Feedback/VisualFeedback.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    eventStatuses: {
        type: Object,
        required: true
    },
    enable_status: {
        type: Boolean,
        required: true
    },
    enable_admission: {
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
    enable_status: props.enable_status,
    enable_admission: props.enable_admission
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

watch(() => settingsForm.enable_admission, () => {
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
