<template>
    <jet-dialog-modal :show="true" @close="closeModal(false)">
        <template #content>
            <img src="/Svgs/Overlays/illu_appointment.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 mt-2 mb-8">
                    {{ $t('Event')}}
                </div>
                <!-- Type -->
                <div class=" w-full flex truncate">
                    <div>
                        <div class="block w-10 h-10 rounded-full" :style="{'backgroundColor' : eventType?.hex_code }" />
                    </div>
                    <p class="ml-2 flex items-center text-lg font-lexend font-semibold">
                        {{ eventType?.name }}
                    </p>
                </div>

                <div class="flex flex-wrap">
                    <!-- Project and Creator -->
                    <div class="my-2 flex w-full">
                        <div class="w-1/2 flex items-center my-auto" v-if="this.project?.id">
                            {{ $t('assigned to')}}: <a
                            :href="route('projects.tab', {project: this.project.id, projectTab: this.first_project_calendar_tab_id})"
                            class="ml-3 mt-1 items-center flex linkText">
                            {{ this.project?.name }}
                        </a>
                        </div>
                        <div class="flex items-center">
                            erstellt von {{ this.creator.first_name }} {{ this.creator.last_name }}
                            <img v-if="this.creator"
                                 :data-tooltip-target="this.creator.id"
                                 :src="this.creator.profile_photo_url"
                                 :alt="this.creator.last_name"
                                 class="ml-2 my-auto ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                            <div class="xsLight ml-3" v-else>
                                {{ $t('Deleted user')}}
                            </div>
                        </div>
                    </div>
                    <!-- Time -->
                    <div class="my-2 flex w-full">
                        <div
                            v-if="this.getDateOfDate(this.request.start_time) === this.getDateOfDate(this.request.end_time)">
                            {{ this.formatDate(this.request.start_time) }} -
                            {{ this.getTimeOfDate(this.request.end_time) }}
                        </div>
                        <div v-else>
                            {{ this.formatDate(this.request.start_time) }} -
                            {{ this.formatDate(this.request.end_time) }}
                        </div>
                    </div>
                    <!--    Properties    -->
                    <div class="flex py-2 w-full">
                        <div v-if="request.audience">
                            <TagComponent icon="audience" :displayed-text="$t('With audience')" hideX="true"></TagComponent>
                        </div>
                        <div v-if="request.is_loud">
                            <TagComponent :displayed-text="$t('It gets loud')" hideX="true"></TagComponent>
                        </div>
                    </div>
                    <!-- Description -->
                    <div class="mt-4 xsDark flex w-full">
                        {{ this.request.description }}
                    </div>
                    <div class="py-1 flex w-11/12">
                        <Listbox as="div" class="w-full" v-model="this.room" id="room">
                            <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                                <div class="flex-grow text-left xsDark">
                                    {{ this.room?.name }}
                                </div>
                                <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </ListboxButton>
                            <ListboxOptions class="w-10/12 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                <ListboxOption v-for="roomOption in rooms"
                                               class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                               :key="roomOption.name"
                                               :value="roomOption"
                                               v-slot="{ active, selected }">
                                    <div :class="[selected ? 'xsWhiteBold' : 'xsLight']">
                                        {{ roomOption.name }}
                                    </div>
                                    <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                </ListboxOption>
                            </ListboxOptions>
                        </Listbox>
                    </div>
                </div>
                <IconX stroke-width="1.5" @click="closeModal(false)"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="flex justify-center mt-6">
                    <FormButton @click="closeModal(true)" :text="$t('Confirm occupancy with room change')"
                    />
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>

import 'vue-cal/dist/vuecal.css'
import JetDialogModal from "@/Jetstream/DialogModal";
import {ChevronDownIcon, XIcon} from '@heroicons/vue/outline';
import {CheckIcon} from "@heroicons/vue/solid";
import TagComponent from "@/Layouts/Components/TagComponent";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import Permissions from "@/mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/mixins/IconLib.vue";

export default {
    name: 'AnswerEventRequestWithRoomChangeComponent',
    mixins: [Permissions, IconLib],
    components: {
        FormButton,
        JetDialogModal,
        XIcon,
        CheckIcon,
        TagComponent,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon,
    },
    props: [
        'type',
        'request',
        'rooms',
        'projects',
        'eventTypes',
        'creator',
        'first_project_calendar_tab_id'
    ],
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.request.room_id = this.room.id;
            this.request.occupancy_option = false;
            this.$emit('closed', bool);
        },
        formatDate(isoDate) {
            if(isoDate?.split('T').length > 1){
                return isoDate.split('T')[0].substring(8, 10) + '.' + isoDate.split('T')[0].substring(5, 7) + '.' + isoDate.split('T')[0].substring(0, 4) + ', ' + isoDate.split('T')[1].substring(0, 5)
            }else if(isoDate?.split(' ').length > 1){
                return isoDate.split(' ')[0].substring(8, 10) + '.' + isoDate.split(' ')[0].substring(5, 7) + '.' + isoDate.split(' ')[0].substring(0, 4) + ', ' + isoDate.split(' ')[1].substring(0, 5)
            }
        },
        getTimeOfDate(isoDate) {
            return isoDate.split('T')[1].substring(0, 5);
        },
        getDateOfDate(isoDate) {
            return isoDate.split('T')[0];
        },
    },
    data() {
        return {
            eventType: this.eventTypes.find(eventType => eventType.id === this.request.event_type_id),
            room: this.rooms.find(room => room.id === this.request.room_id),
            project: this.projects.find(project => project.id === this.request.project_id),
        }
    }
}
</script>

<style scoped></style>
