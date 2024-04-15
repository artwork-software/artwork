<template>
    <div>
        <div class="flex items-center gap-x-5">
            <div class="sLight">
                {{ $t('Contact persons') }}
            </div>
            <div v-if="this.canEditComponent">
                <PencilAltIcon class=" w-5 h-5 rounded-full" :class="inSidebar ? 'text-white' : 'text-artwork-buttons-context'"
                               @click="openContactModal"/>
            </div>
        </div>
        <div v-if="this.project?.project_managers?.length > 0" class="my-2" v-for="projectManager in this.project?.project_managers">
            <div class="flex w-full">
                <div class="mr-4">
                    <img :data-tooltip-target="projectManager?.id" :src="projectManager?.profile_photo_url"
                         :alt="projectManager?.name"
                         class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                </div>
                <div>
                    <div>
                        <div class="xsLight">
                            {{ projectManager?.first_name }} {{ projectManager?.last_name }}
                            <span class="xxsLight">
                                    , {{ $t('Project management')}}
                                </span>
                        </div>
                        <div>
                            <div>
                                {{ projectManager?.phone_number }}
                            </div>
                            <div v-if="projectManager?.phone_number">
                                |
                            </div>
                            <div class="xxsLight">
                                {{ projectManager?.email }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="this.project?.shift_contacts?.length > 0" class="my-2" v-for="contact in this.project?.shift_contacts">
            <div class="flex w-full">
                <div class="mr-4">
                    <img :src="contact?.profile_photo_url" :alt="contact?.name"
                         class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                </div>
                <div>
                    <div>
                        <div class="xsLight">
                            {{ contact?.first_name }} {{ contact?.last_name }}
                        </div>
                        <div>
                            <div>
                                {{ contact?.phone_number }}
                            </div>
                            <div v-if="contact?.phone_number">
                                |
                            </div>
                            <div class="xxsLight">
                                {{ contact?.email }}
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
            :project-managers="this.project?.project_managers"
            @close-modal="closeContactModal"
        />
    </div>
</template>

<script>
import {defineComponent} from "vue";
import {PencilAltIcon} from "@heroicons/vue/outline";
import ShiftContactModal from "@/Layouts/Components/ShiftContactModal.vue";
import {IconEdit} from "@tabler/icons-vue";

export default defineComponent({
    components: {
        IconEdit,
        ShiftContactModal,
        PencilAltIcon
    },
    props: [
        'project',
        'inSidebar',
        'canEditComponent'
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
