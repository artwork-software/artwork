<template>
    <div class="print:w-full flex relative"
         :class="[event?.isNew ? 'border-2 rounded-lg border-pink-500 border-dashed w-max py-2 px-1' : '']">
        <div class="flex items-center gap-4 relative" >
            <div class="flex items-center justify-center pr-2 pl-1" v-if="multiEdit">
                <input
                    v-model="event.isSelectedForMultiEdit"
                    aria-describedby="candidates-description"
                    name="candidates" type="checkbox"
                    :id="event.id"
                    class="input-checklist"
                />
            </div>
            <div v-if="event.isSelectedForMultiEdit && multiEdit"
                 class="absolute pointer-events-none top-0 left-0 w-full h-full bg-green-100/20 z-50" />
            <div v-if="event.is_planning"
                 class="absolute pointer-events-none top-0 left-0 w-full h-full bg-blue-500/10 rounded-r-lg -z-10" />
            <div :style="getColumnSize(1)" v-if="usePage().props.event_status_module">
                <Listbox v-model="event.status"
                         @update:model-value="updateEventInDatabase"
                         :id="'status-' + index"
                         as="div"
                         class="relative"
                         :disabled="canEditComponent === false">
                    <Float auto-placement :offset="4" class="relative w-fit" floating-as="div">
                        <ListboxButton :id="'status-' + index + 'button'" @click="storeFocus('status-' + index + 'button', 'listbox')" :class="[canEditComponent ? '' : 'bg-gray-100', 'menu-button']"
                                       class="print:border-0">
                            <div class="flex items-center gap-x-2">
                                <div>
                                    <div class="block w-5 h-5 rounded-full"
                                         :style="{'backgroundColor' : event.status?.color }"/>
                                </div>
                                <div class="truncate print:w-full" :style="getColumnTextSize(1)">
                                    {{ event.status?.name }}
                                </div>
                            </div>
                            <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary print:hidden"
                                             aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions class="w-fit rounded-lg bg-primary max-h-56 overflow-y-auto text-sm z-30">
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
                    </Float>
                </Listbox>
            </div>
            <div :style="getColumnSize(2)">
                <Listbox v-model="event.type"
                         @update:model-value="updateEventInDatabase"
                         :id="'type-'+ index"
                         as="div"
                         class="relative w-full"
                         :disabled="canEditComponent === false">
                    <Float auto-placement :offset="4" class="relative w-fit" floating-as="div">
                        <ListboxButton :id="'type-'+ index + 'button'" @click="storeFocus('type-' + index + 'button', 'listbox')" :class="[canEditComponent ? '' : 'bg-gray-100', 'menu-button']" class="print:border-0 active:ring-primary active:ring-1">
                            <span class="flex items-center gap-x-2">
                                <span class="">
                                    <span class="block w-5 h-5 rounded-full" :style="{'backgroundColor' : event.type?.hex_code }"/>
                                </span>
                                <span class="truncate print:w-full flex items-center" :style="getColumnTextSize(2)">
                                    {{ event.type?.name }}
                                </span>
                                <ToolTipComponent icon="IconCalendarCog" v-if="event.is_planning" class="-ml-8" :tooltip-text="'Dies ist ein geplanter Termin'">
                </ToolTipComponent>
                            </span>
                            <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary print:hidden"
                                             aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions
                            class="w-fit rounded-lg bg-primary max-h-56 h-full overflow-y-auto text-sm z-30">
                            <ListboxOption
                                class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                v-for="eventType in sortedEventTypes"
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
                    </Float>
                </Listbox>

            </div>
            <div :style="getColumnSize(3)">
                <BaseInput v-model="event.name"
                       type="text"
                       :id="'name-' + index"
                       :class="event.type?.individual_name && !event.name ? 'border-red-500' : ''"
                       label="Name"
                       @mousedown="storeFocus('name-' + index)"
                       @focusout="updateEventInDatabase"
                       :disabled="canEditComponent === false"
                />
            </div>
            <div :style="getColumnSize(4)">
                <Listbox :id="'room-' + index"
                         as="div"
                         class="relative"
                         v-model="event.room"
                         @update:model-value="updateEventInDatabase"
                         :disabled="canEditComponent === false">
                    <Float auto-placement :offset="4" class="relative w-fit" floating-as="div">
                        <ListboxButton :id="'room-'+ index + 'button'" @click="storeFocus('room-' + index + 'button', 'listbox')" :class="[canEditComponent ? '' : 'bg-gray-100', 'menu-button']"
                                       class=" print:border-0">
                            <span class="" :style="getColumnTextSize(4)">
                                {{ event.room?.name }}
                            </span>
                            <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary print:hidden"
                                             aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions class="w-fit rounded-lg bg-primary max-h-56 overflow-y-auto text-sm z-30">
                            <ListboxOption v-for="room in sortedRooms"
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
                    </Float>
                </Listbox>
            </div>
            <div class="print:col-span-2" :style="getColumnSize(5)">
                <div class="relative">
                    <BaseInput
                        v-model="event.day"
                        type="date"
                        :id="'day-' + index"
                        :label="'Tag ' + dayString"
                        :disabled="canEditComponent === false"
                        @mousedown="storeFocus('day-' + index)"
                        @focusout="updateEventInDatabase"
                        @change="dayString = getDayOfWeek(new Date(event.day)).replace('.', '')"
                    />
                </div>
            </div>
            <div class="col-span-2" :style="getColumnSize(6)">
                <div class="flex items-center" v-if="timeArray">
                    <BaseInput
                        v-model="event.start_time"
                        type="time"
                        :id="'start-time-' + index"
                        label="Start"
                        class="rounded-r-none print:border-0"
                        :disabled="canEditComponent === false"
                        @mousedown="storeFocus('start-time-' + index)"
                        @focusout="updateEventInDatabase"
                    />
                    <BaseInput
                        v-model="event.end_time"
                        type="time"
                        :id="'end_time-' + index"
                        label="End"
                        class="!rounded-l-none border-l-0 print:border-0"
                        :disabled="canEditComponent === false"
                        @focusout="updateEventInDatabase"
                        @mousedown="storeFocus('end_time-' + index)"
                    />
                </div>
            </div>
            <div v-if="canEditComponent" class="flex items-center col-span-1 print:hidden">
                <div class="flex items-center gap-x-3">
                    <ToolTipComponent icon="IconNote" v-if="!isInModal" :tooltip-text="$t('Edit the description')"
                                      stroke="1.5" @click="openNoteModal = true"/>
                    <ToolTipDefault :tooltip-text="$t('Set the event to all-day')" left show24-h-icon
                                    icon-classes="w-6 h-6"
                                    v-if="event.start_time && event.end_time && !event.copy && !isInModal"
                                    @click="removeTime"/>
                    <BaseMenu show-custom-icon dots-color="!text-artwork-buttons-context" stroke-width="2"
                              icon="IconCopy" translation-key="Copy" menu-width="w-fit" white-menu-background>
                        <div class="flex items-center gap-x-2 p-3">
                            <IconPlus class="w-6 h-6 min-w-6 min-h-6 text-artwork-buttons-context" stroke-width="2"/>
                            <BaseInput
                                type="number"
                                label="Anzahl"
                                v-model="event.copyCount"
                                min="1"
                                minlength="1"
                                max="1000"
                                id="amount"/>
                            <Listbox as="div" class="relative" v-model="event.copyType" id="room">
                                <ListboxButton class="menu-button">
                                    <div class="flex-grow flex text-left xsDark !w-12 truncate">
                                        {{ event.copyType?.name }}
                                    </div>
                                    <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary"
                                                     aria-hidden="true"/>
                                </ListboxButton>
                                <ListboxOptions
                                    class="w-44 rounded-lg bg-primary max-h-32 overflow-y-auto text-sm absolute z-30">
                                    <ListboxOption v-for="copyType in copyTypes"
                                                   class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                                   :key="copyType.name"
                                                   :value="copyType"
                                                   v-slot="{ active, selected }">
                                        <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                            {{ copyType.name }}
                                        </div>
                                        <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                                   aria-hidden="true"/>
                                    </ListboxOption>
                                </ListboxOptions>
                            </Listbox>
                            <IconCircleCheckFilled @click="createCopyByEventWithData(event)"
                                                   class="w-8 h-8 min-w-6 min-h-6 text-artwork-buttons-create cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out"
                                                   stroke-width="2"/>
                            <IconX @click="event.copy = false"
                                   class="w-6 h-6 min-w-6 min-h-6 text-artwork-buttons-context cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out"
                                   stroke-width="2"/>
                        </div>
                    </BaseMenu>
                    <BaseMenu has-no-offset white-menu-background v-if="!isInModal">
                        <BaseMenuItem white-menu-background icon="IconEdit" title="Edit" @click="openEventComponent(event.id)"/>
                        <BaseMenuItem white-menu-background v-if="index > 0 && !event.copy || !isInModal" icon="IconTrash"
                                      title="Put in the trash" @click="openDeleteEventConfirmModal"/>
                    </BaseMenu>
                </div>
            </div>
        </div>
        <div v-if="event.nameError && !event.name" class="text-xs mt-1 text-artwork-messages-error">
            {{ $t('Event name is mandatory') }}
        </div>
        <confirmation-component
            v-if="showDeleteEventConfirmModal"
            :confirm="$t('Delete')"
            :titel="$t('Delete event')"
            :description="$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.')"
            @closed="onCloseDeleteEventConfirmModal"/>

        <AddEditEventNoteModal :event="event" v-if="openNoteModal" @close="openNoteModal = false"/>

    </div>
</template>

<script setup>
import {
    IconCalendarCog,
    IconCheck,
    IconChevronDown,
    IconCircleCheckFilled,
    IconCopy,
    IconDotsVertical,
    IconEdit,
    IconPlus,
    IconTrash,
    IconX
} from "@tabler/icons-vue";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import {router, usePage} from "@inertiajs/vue3";
import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import {computed, nextTick, onMounted, ref} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import AddEditEventNoteModal from "@/Pages/Projects/Components/BulkComponents/AddEditEventNoteModal.vue";
import {inject} from "vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {Float} from "@headlessui-float/vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    timeArray: {
        type: Boolean,
        required: true
    },
    event_types: {
        type: Object,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    copyTypes: {
        type: Array,
        required: true
    },
    index: {
        type: Number,
        required: true
    },
    isInModal: {
        type: Boolean,
        required: false,
        default: false
    },
    canEditComponent: {
        type: Boolean,
        required: true
    },
    eventStatuses: {
        type: Object,
        required: true
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    }
});

const showMenu = ref(false);
const dayString = ref(null);
const openNoteModal = ref(false);
const event_properties = inject('event_properties');

const emit = defineEmits(['deleteCurrentEvent', 'createCopyByEventWithData', 'openEventComponent']);
const openEventComponent = (eventId) => {
    emit.call(this, 'openEventComponent', eventId)
};

const getColumnSize = (column) => {
    return {
        minWidth: usePage().props.auth.user.bulk_column_size[column] + 'px',
        width: usePage().props.auth.user.bulk_column_size[column] + 'px',
        maxWidth: usePage().props.auth.user.bulk_column_size[column] + 'px'
    }
}
const getColumnTextSize = (column) => {
    return {
        minWidth: parseInt(usePage().props.auth.user.bulk_column_size[column]) - 50 + 'px',
        width: parseInt(usePage().props.auth.user.bulk_column_size[column]) - 50 + 'px',
        maxWidth: parseInt(usePage().props.auth.user.bulk_column_size[column]) - 50 + 'px'
    }
}

const createCopyByEventWithData = (event) => {
    emit('createCopyByEventWithData', event);
}

const showDeleteEventConfirmModal = ref(false);

const openDeleteEventConfirmModal = () => {
    showDeleteEventConfirmModal.value = true;
};

const onCloseDeleteEventConfirmModal = (closedOnPurpose) => {
    if (closedOnPurpose) {
        emit('deleteCurrentEvent', props.event);
    }

    showDeleteEventConfirmModal.value = false;
};

const updateEventInDatabase = async () => {
    if (props.event.id) {
        if (props.event.start_time && !props.event.end_time) {
            const startTime = new Date(`01/01/2000 ${props.event.start_time}`);
            startTime.setMinutes(startTime.getMinutes() + 30);
            props.event.end_time = startTime.toTimeString().slice(0, 5);
        }

        if (!props.event.start_time && props.event.end_time) {
            const endTime = new Date(`01/01/2000 ${props.event.end_time}`);
            endTime.setMinutes(endTime.getMinutes() - 30);
            props.event.start_time = endTime.toTimeString().slice(0, 5);
        }

        if (props.event.type?.individual_name && !props.event.name) {
            props.event.nameError = true;
            return;
        }

        router.patch(route('event.update.single.bulk', {event: props.event.id}), {
            data: props.event
        }, {
            preserveState: false,
            preserveScroll: true,
            onFinish: () => {
                nextTick(() => {
                    setTimeout(() => {
                        if (lastFocusedField.value) {
                            const field = document.getElementById(lastFocusedField.value);
                            if (field) {
                                if(lastFocusedField.type === 'listbox') {
                                    field.click()
                                }else{
                                    field.focus();
                                }

                            }
                        }
                    }, 400);
                })
            }
        });
    }
}

const lastFocusedField = ref(null);
const storeFocus = (fieldId, type) => {
    console.log(fieldId, type);
    lastFocusedField.value = fieldId;
    if(type){
        lastFocusedField.type = type;
    }
};

const sortedRooms = computed(() => {
    return props.rooms.sort((a, b) => a.name.localeCompare(b.name));
})

const sortedEventTypes = computed(() => {
    return props.event_types.sort((a, b) => a.name.localeCompare(b.name));
})

const removeTime = () => {
    props.event.start_time = null;
    props.event.end_time = null;
    updateEventInDatabase();
}

const getDayOfWeek = (date) => {
    const days = ['So.', 'Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.'];
    return days[date.getDay()];
}

onMounted(() => {
    dayString.value = getDayOfWeek(new Date(props.event.day)).replace('.', '')
});
</script>
