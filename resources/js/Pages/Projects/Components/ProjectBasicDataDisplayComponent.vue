<template>
    <div>
        <div class="flex items-center gap-x-3">
            <div
                class="font-lexend font-black tracking-wide"
                :class="inSidebar ? 'text-white text-lg' : 'text-primaryText text-xl'"
            >
                {{ project?.name }} Basisdaten
            </div>

            <PropertyIcon
                v-if="canEditBasicData"
                name="IconEdit"
                class="w-5 h-5 rounded-full cursor-pointer"
                :class="inSidebar ? 'text-white' : 'text-artwork-buttons-context'"
                @click="openEditProjectModal"
            />
        </div>

        <div class="mt-4 space-y-3">
            <div v-for="row in rows" :key="row.key" class="space-y-0.5">
                <div
                    class="text-[11px] font-semibold uppercase tracking-wide"
                    :class="inSidebar ? 'text-zinc-200' : 'text-secondary'"
                >
                    {{ row.label }}
                </div>
                <div :class="inSidebar ? 'text-white text-sm' : 'text-primaryText text-sm'">
                    {{ row.value }}
                </div>
            </div>
        </div>

        <ProjectDataEditModal
            v-if="editingProject"
            :show="editingProject"
            :project="project"
            :group-projects="headerObject.groupProjects"
            :current-group="headerObject.currentGroup"
            :states="headerObject.states"
            :create-settings="createSettings"
            @closed="closeEditProjectModal"
        />
    </div>
</template>

<script>
import {defineComponent} from 'vue';
import {can, is} from 'laravel-permission-to-vuejs';
import ProjectDataEditModal from '@/Layouts/Components/ProjectDataEditModal.vue';
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue';

export default defineComponent({
    name: 'ProjectBasicDataDisplayComponent',
    components: {PropertyIcon, ProjectDataEditModal},
    props: {
        project: {
            type: Object,
            required: true
        },
        headerObject: {
            type: Object,
            required: true
        },
        createSettings: {
            type: Object,
            required: false,
            default: () => ({})
        },
        inSidebar: {
            type: Boolean,
            required: false,
            default: false
        },
        projectCategories: {
            type: Array,
            required: false,
            default: () => ([])
        },
        projectGenres: {
            type: Array,
            required: false,
            default: () => ([])
        },
        projectSectors: {
            type: Array,
            required: false,
            default: () => ([])
        },
    },
    data() {
        return {
            editingProject: false,
        };
    },
    computed: {
        canEditBasicData() {
            const userId = this.$page?.props?.auth?.user?.id;

            return (
                can('write projects') ||
                is('artwork admin') ||
                this.headerObject.projectManagerIds?.includes(userId) ||
                this.headerObject.projectWriteIds?.includes(userId)
            );
        },
        propertiesString() {
            const names = [
                ...(this.projectCategories ?? []).map((c) => c?.name).filter(Boolean),
                ...(this.projectGenres ?? []).map((g) => g?.name).filter(Boolean),
                ...(this.projectSectors ?? []).map((s) => s?.name).filter(Boolean),
            ];

            return names.join(', ');
        },
        rows() {
            const rows = [];

            if (this.project?.name) {
                rows.push({
                    key: 'name',
                    label: this.$t('Project name'),
                    value: this.project.name
                });
            }

            if (this.project?.artists) {
                rows.push({
                    key: 'artists',
                    label: this.$t('Artists'),
                    value: this.project.artists
                });
            }

            if (this.propertiesString) {
                rows.push({
                    key: 'properties',
                    label: this.$t('Project properties'),
                    value: this.propertiesString
                });
            }

            if (this.project?.state?.name) {
                rows.push({
                    key: 'state',
                    label: this.$t('Project status'),
                    value: this.project.state.name
                });
            }

            if (this.project?.cost_center?.name) {
                rows.push({
                    key: 'cost_center',
                    label: this.$t('Name of the cost unit'),
                    value: this.project.cost_center.name
                });
            }

            if (this.project?.budget_deadline) {
                rows.push({
                    key: 'budget_deadline',
                    label: this.$t('Budget deadline'),
                    value: this.project.budget_deadline
                });
            }

            return rows;
        }
    },
    methods: {
        openEditProjectModal() {
            this.editingProject = true;
        },
        closeEditProjectModal() {
            this.editingProject = false;
        }
    }
});
</script>
