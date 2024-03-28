<template>
    <div>
        <div class="flex justify-between">
            <div class="sLight">
                {{ $t('Contact persons') }}
            </div>
            <div>
                <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                               @click="openContactModal"/>
            </div>
        </div>
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
        <ShiftContactModal
            :show="showContactModal"
            :assigned-shift-contacts="this.project.shift_contacts"
            :project-id="this.project.id"
            :project-managers="this.project.project_managers"
            @close-modal="closeContactModal"
        />
    </div>
</template>

<script>
import {defineComponent} from "vue";
import {PencilAltIcon} from "@heroicons/vue/outline";
import ShiftContactModal from "@/Layouts/Components/ShiftContactModal.vue";

export default defineComponent({
    components: {
        ShiftContactModal,
        PencilAltIcon
    },
    props: [
        'project'
    ],
    data() {
        return {
            showContactModal: false
        };
    },
    methods: {
        openContactModal() {
            this.showContactModal = true
        },
        closeContactModal() {
            this.showContactModal = false
        },
    }
});
</script>
