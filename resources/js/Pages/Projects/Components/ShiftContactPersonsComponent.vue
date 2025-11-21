<template>
    <div>
        <div class="flex items-center gap-x-5">
            <BasePageTitle title="Contact persons" :white-text="inSidebar" />
            <div v-if="this.canEditComponent" class="mt-2">
                <PencilAltIcon class=" w-5 h-5 rounded-full" :class="inSidebar ? 'text-white' : 'text-artwork-buttons-context'"
                               @click="openContactModal"/>
            </div>
        </div>
        <div v-if="loadError" class="text-xs text-rose-600 mt-2">
            {{ loadError }}
        </div>
        <div v-else-if="loading" class="text-xs text-secondary mt-2">
            {{ $t('Loading data...') }}
        </div>
        <div v-if="shiftProject?.project_managers?.length > 0" class="my-2" v-for="projectManager in shiftProject?.project_managers">
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
        <div v-if="shiftProject?.shift_contacts?.length > 0" class="my-2" v-for="contact in shiftProject?.shift_contacts">
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
            :assigned-shift-contacts="shiftProject?.shift_contacts ?? []"
            :project-id="currentProjectId()"
            :project-managers="shiftProject?.project_managers ?? []"
            @close-modal="closeContactModal"
        />
    </div>
</template>

<script>
import {defineComponent} from "vue";
import axios from "axios";
import {PencilAltIcon} from "@heroicons/vue/outline";
import ShiftContactModal from "@/Layouts/Components/ShiftContactModal.vue";
import {IconEdit} from "@tabler/icons-vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";

export default defineComponent({
    components: {
        BasePageTitle,
        IconEdit,
        ShiftContactModal,
        PencilAltIcon
    },
    props: {
        project: {
            type: Object,
            default: null
        },
        projectId: {
            type: [Number, String],
            default: null
        },
        inSidebar: {
            type: Boolean,
            default: false
        },
        canEditComponent: {
            type: Boolean,
            default: false
        }
    },
    data() {
        const hasInitialData =
            this.project &&
            (Array.isArray(this.project.shift_contacts) || Array.isArray(this.project.project_managers));
        return {
            showContactModal: false,
            loading: false,
            loadError: null,
            localProject: hasInitialData ? this.project : null
        };
    },
    computed: {
        shiftProject() {
            return this.localProject ?? this.project ?? {};
        }
    },
    watch: {
        project: {
            deep: true,
            immediate: true,
            handler(newProject) {
                if (this.hasShiftContactsData(newProject)) {
                    this.localProject = newProject;
                }
            }
        }
    },
    mounted() {
        this.ensureShiftContactsData();
    },
    methods: {
        async ensureShiftContactsData() {
            if (this.hasShiftContactsData(this.shiftProject)) {
                return;
            }

            const id = this.currentProjectId();
            if (!id) {
                return;
            }

            this.loading = true;
            this.loadError = null;

            try {
                const response = await axios.get(route('projects.tabs.shift-contacts', {project: id}));
                this.localProject = {
                    ...this.project,
                    shift_contacts: response.data.shift_contacts ?? [],
                    project_managers: response.data.project_managers ?? []
                };
            } catch (e) {
                this.loadError = this.$t
                    ? this.$t('Kontaktdaten konnten nicht geladen werden.')
                    : 'Failed to load contact data.';
            } finally {
                this.loading = false;
            }
        },
        hasShiftContactsData(project) {
            return project && (
                (Array.isArray(project.shift_contacts) && project.shift_contacts.length > 0) ||
                (Array.isArray(project.project_managers) && project.project_managers.length > 0)
            );
        },
        currentProjectId() {
            return this.project?.id ?? this.projectId ?? this.shiftProject?.id ?? null;
        },
        openContactModal() {
            this.showContactModal = true
        },
        closeContactModal() {
            this.showContactModal = false
        },
    }
});
</script>
