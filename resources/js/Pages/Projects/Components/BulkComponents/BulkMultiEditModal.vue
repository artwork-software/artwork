<template>
    <BaseModal @closed="$emit('close')">

        <div>
            <ModalHeader
                :title="$t('Bulk Multi Edit')"
                :description="$t('Edit multiple events at once')"
            />
        </div>

        <div v-if="multiEditForm.eventIds.length === 0">
            <div class="rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="shrink-0">
                        <component is="IconMoodWrrr" class="size-5 text-red-400" aria-hidden="true"/>
                    </div>
                    <div class="ml-3 flex-1 md:flex md:justify-between">
                        <p class="text-sm text-red-700">
                            {{ $t('No events selected') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="validationErrors.length > 0" class="mb-4">
            <div class="rounded-md bg-red-50 p-3">
                <div class="text-sm text-red-700">
                    <div v-for="error in validationErrors" :key="error" class="mb-1 last:mb-0">
                        {{ $t(error) }}
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" v-if="multiEditForm.eventIds.length > 0">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="" v-if="usePage().props.event_status_module">
                    <div class="xsDark mb-1">
                        {{ $t('Event Status') }}
                    </div>
                    <Listbox v-model="multiEditForm.selectedEventStatus"
                             id="type"
                             as="div"
                             class="relative">
                        <ListboxButton :class="['menu-button']" class="print:border-0">
                            <div class="flex items-center gap-x-2">
                                <div v-if="multiEditForm?.selectedEventStatus?.color">
                                    <div class="block w-5 h-5 rounded-full"
                                         :style="{'backgroundColor' : multiEditForm?.selectedEventStatus?.color }"/>
                                </div>
                                <div class="truncate w-full xsDark">
                                    {{ multiEditForm?.selectedEventStatus?.name ?? $t('No change') }}
                                </div>
                            </div>
                            <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary print:hidden"
                                             aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions
                            class="w-full rounded-lg bg-primary max-h-56 overflow-y-auto text-sm absolute z-30">
                            <ListboxOption
                                class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                v-for="status in eventStatuses"
                                :key="status.name"
                                :value="status"
                                v-slot="{ active, selected }">
                                <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']"
                                     class="flex items-center gap-x-2">
                                    <div>
                                        <div class="block w-3 h-3 rounded-full"
                                             :style="{'backgroundColor' : status?.color }"/>
                                    </div>
                                    {{ status.name }}
                                </div>
                                <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                           aria-hidden="true"/>
                            </ListboxOption>
                        </ListboxOptions>
                    </Listbox>
                </div>
                <div class="">
                    <div class="xsDark mb-1">
                        {{ $t('Event type') }}
                    </div>
                    <Listbox v-model="multiEditForm.selectedEventType"
                             id="type"
                             as="div"
                             class="relative"
                    >
                        <ListboxButton :class="['menu-button']" class="print:border-0 ">
                            <div class="flex items-center gap-x-2">
                                <div class="" v-if="multiEditForm?.selectedEventType?.hex_code">
                                    <div class="block w-5 h-5 rounded-full"
                                         :style="{'backgroundColor' : multiEditForm?.selectedEventType?.hex_code }"/>
                                </div>
                                <div class="truncate xsDark">
                                    {{ multiEditForm?.selectedEventType?.name ?? $t('No change') }}
                                </div>
                            </div>
                            <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary print:hidden"
                                             aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions
                            class="w-full rounded-lg bg-primary max-h-56 overflow-y-auto text-sm absolute z-30">
                            <ListboxOption
                                class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                v-for="eventType in eventTypes"
                                :key="eventType.name"
                                :value="eventType"
                                v-slot="{ active, selected }">
                                <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']"
                                     class="flex items-center gap-x-2">
                                    <div>
                                        <div class="block w-3 h-3 rounded-full"
                                             :style="{'backgroundColor' : eventType?.hex_code }"/>
                                    </div>
                                    {{ eventType.name }}
                                </div>
                                <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                           aria-hidden="true"/>
                            </ListboxOption>
                        </ListboxOptions>
                    </Listbox>
                </div>
                <div>
                    <div class="xsDark mb-1">
                        {{ $t('Room') }}
                    </div>
                    <Listbox id="room"
                             as="div"
                             class="relative"
                             v-model="multiEditForm.selectedRoom"
                    >
                        <ListboxButton :class="['menu-button']" class=" print:border-0">
                            <div class="flex-grow flex text-left xsDark">
                                {{ multiEditForm.selectedRoom?.name ?? $t('No change') }}
                            </div>
                            <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary print:hidden"
                                             aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions
                            class="w-full rounded-lg bg-primary max-h-56 overflow-y-auto text-sm absolute z-30">
                            <ListboxOption v-for="room in rooms"
                                           class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                           :key="room.name"
                                           :value="room"
                                           v-slot="{ active, selected }">
                                <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                    {{ room.name }}
                                </div>
                                <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                           aria-hidden="true"/>
                            </ListboxOption>
                        </ListboxOptions>
                    </Listbox>
                </div>
                <div class="col-span-2">
                    <div class="mb-2">
                        <BaseInput
                            v-model="multiEditForm.eventName"
                            type="text"
                            id="event-name"
                            :label="$t('Event name')"
                            placeholder="Name"
                        />
                    </div>
                    <div class="relative mt-1">
                        <BaseInput
                            v-model="multiEditForm.selectedDay"
                            type="date"
                            id="event-day"
                            :label="$t('Day')"
                            placeholder="Tag"
                        />
                    </div>
                </div>
                <div class="col-span-2">
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <BaseInput
                                v-model="multiEditForm.selectedStartTime"
                                type="time"
                                id="event-start-time"
                                :label="$t('Start time')"
                                placeholder="Start"
                            />
                        </div>
                        <div class="flex-1">
                            <BaseInput
                                v-model="multiEditForm.selectedEndTime"
                                type="time"
                                id="event-end-time"
                                :label="$t('End time')"
                                placeholder="End"
                            />
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex items-center justify-between mt-5">
                <FormButton class="bg-red-500 hover:bg-red-600" @click="$emit('close')" type="button"
                            :text="$t('Cancel')"/>
                <FormButton type="submit" :text="$t('Save')" :disabled="multiEditForm.eventIds.length === 0"/>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {IconChevronDown, IconCheck, IconAlertCircle} from "@tabler/icons-vue";
import {onMounted, ref} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {useI18n} from "vue-i18n";

const props = defineProps({
    eventIds: {
        type: Array,
        required: true
    },
    eventTypes: {
        type: Array,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    eventStatuses: {
        type: Object,
        required: false
    }
})

const emits = defineEmits(['close']);

const { t } = useI18n();
const validationErrors = ref([]);

const multiEditForm = useForm({
    eventIds: props.eventIds,
    selectedEventType: null,
    selectedRoom: null,
    selectedEventStatus: null,
    eventName: '',
    selectedDay: '',
    selectedStartTime: '',
    selectedEndTime: '',
})

const submit = () => {
    // Clear previous validation errors
    validationErrors.value = [];

    // Validate event name for individual_name event types
    if (multiEditForm?.selectedEventType?.individual_name && !multiEditForm.eventName) {
        validationErrors.value.push('Event name is required for this event type');
    }

    // If there are validation errors, don't submit
    if (validationErrors.value.length > 0) {
        return false;
    }

    // wenn eine Startzeit angegeben ist, aber keine Endzeit, dann wird die Startzeit +30 min als Endzeit genommen
    if (multiEditForm.selectedStartTime && !multiEditForm.selectedEndTime) {
        const startTime = multiEditForm.selectedStartTime.split(':');
        const endTime = new Date();
        endTime.setHours(startTime[0]);
        endTime.setMinutes(startTime[1]);
        endTime.setMinutes(endTime.getMinutes() + 30);
        multiEditForm.selectedEndTime = `${endTime.getHours()}:${endTime.getMinutes()}`;
    }

    /*multiEditForm.post(route('events.bulk-multi-edit'), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            emits('close');
        },
        onError: (errors) => {
            // Handle server-side validation errors
            validationErrors.value = Object.values(errors).flat();
        }
    });*/

    axios.post(route('events.bulk-multi-edit'), multiEditForm)
        .then(response => {
            emits('close');
        })
        .catch(error => {
            if (error.response && error.response.data && error.response.data.errors) {
                validationErrors.value = Object.values(error.response.data.errors).flat();
            } else {
                validationErrors.value.push(t('An unexpected error occurred. Please try again.'));
            }
        });
}

</script>

<style scoped>

</style>
