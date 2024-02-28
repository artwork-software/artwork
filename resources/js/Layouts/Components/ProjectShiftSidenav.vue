<template>
    <div class="w-full mt-24">
        <div class="flex justify-between">
            <div class="sLight">
                {{ $t('Relevant dates for shift planning') }}
            </div>
            <div>
                <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                               @click="openShiftRelevantEventTypeModal"/>
            </div>
        </div>
        <div>
            <div class="flex py-2 w-72 flex-wrap">
                <div class="flex" v-for="eventType in this.project.shift_relevant_event_types">
                    <TagComponent type="gray" :displayed-text="eventType.name" hideX="true"></TagComponent>
                </div>
            </div>
        </div>
        <hr class="my-10 border-darkGray">
        <div class="flex justify-between">
            <div class="sLight">
                {{ $t('Contact persons') }}
            </div>
            <div>
                <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                               @click="openContactModal"/>
            </div>
        </div>
        <div>
            <div class="my-2" v-for="projectManager in this.project.project_managers">
                <div class="flex w-full">
                    <div class="mr-4">
                        <img :data-tooltip-target="projectManager?.id" :src="user?.profile_photo_url"
                             :alt="projectManager?.name"
                             class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                    </div>
                    <div>
                        <div>
                            <div class="xsLight">
                                {{ projectManager.first_name }} {{ projectManager.last_name }}
                                <span class="xxsLight">
                                    , {{ $t('Project management')}}
                                </span>
                            </div>
                            <div>
                                <div>
                                    {{ projectManager.phone_number }}
                                </div>
                                <div v-if="projectManager.phone_number">
                                    |
                                </div>
                                <div class="xxsLight">
                                    {{ projectManager.email }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="my-2" v-for="contact in this.project.shift_contacts">
                <div class="flex w-full">
                    <div class="mr-4">
                        <img :src="contact.profile_photo_url" :alt="contact.name"
                             class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                    </div>
                    <div>
                        <div>
                            <div class="xsLight">
                                {{ contact.first_name }} {{ contact.last_name }}
                            </div>
                            <div>
                                <div>
                                    {{ contact.phone_number }}
                                </div>
                                <div v-if="contact.phone_number">
                                    |
                                </div>
                                <div class="xxsLight">
                                    {{ contact.email }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-10 border-darkGray">
        <div class="flex justify-between">
            <div class="sLight">
                {{ $t('General shift information') }}
            </div>
            <div>
                <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                               @click="openShiftInformationModal"/>
            </div>
        </div>
        <div class="mt-2">
            <div class="max-h-48 overflow-y-auto" v-if="project.shiftDescription">
                {{ project.shiftDescription }}
            </div>
            <div v-else>
                {{ $t('No shift information has been entered yet') }}
            </div>
        </div>
    </div>
    <ShiftRelevantEventTypeModal
        :show="showShiftRelevantEventTypeModal"
        @close-modal="closeShiftRelevantEventTypeModal"
        :project="project"
        :event-types="eventTypes"
    />
    <ShiftContactModal
        :show="showContactModal"
        :assigned-shift-contacts="this.project.shift_contacts"
        :project-id="this.project.id"
        :project-managers="this.project.project_managers"
        @close-modal="closeContactModal"
    />
    <ShiftInformationModal
        :show="showShiftInformationModal"
        @close-modal="closeShiftInformationModal"
        :project="project"
    />
</template>

<script>
import {PencilAltIcon} from '@heroicons/vue/outline';
import Permissions from "@/mixins/Permissions.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import ShiftInformationModal from "@/Layouts/Components/ShiftInformationModal.vue";
import ProjectCopyrightModal from "@/Layouts/Components/ProjectCopyrightModal.vue";
import ShiftContactModal from "@/Layouts/Components/ShiftContactModal.vue";
import ShiftRelevantEventTypeModal from "@/Layouts/Components/ShiftRelevantEventTypeModal.vue";

export default {
    mixins: [Permissions],
    name: "ProjectShiftSidenav",
    components: {
        ShiftRelevantEventTypeModal,
        ShiftContactModal,
        ProjectCopyrightModal,
        ShiftInformationModal,
        TagComponent,
        PencilAltIcon,
    },
    props: {
        project: Object,
        eventTypes:Array,
    },
    data() {
        return {
            showShiftInformationModal: false,
            showContactModal: false,
            showShiftRelevantEventTypeModal: false,

        }
    },
    methods: {
        openShiftInformationModal() {
            this.showShiftInformationModal = true
        },
        closeShiftInformationModal() {
            this.showShiftInformationModal = false
        },
        openContactModal() {
            this.showContactModal = true
        },
        closeContactModal() {
            this.showContactModal = false
        },
        openShiftRelevantEventTypeModal(){
            this.showShiftRelevantEventTypeModal = true;
        },
        closeShiftRelevantEventTypeModal(){
            this.showShiftRelevantEventTypeModal = false;
        }
    }
}
</script>
