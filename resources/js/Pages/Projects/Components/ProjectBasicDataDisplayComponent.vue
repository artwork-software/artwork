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

                <!-- default value rendering -->
                <div
                    v-if="row.type === 'text'"
                    :class="inSidebar ? 'text-white text-sm' : 'text-primaryText text-sm'"
                >
                    {{ row.value }}
                </div>

                <!-- properties badges -->
                <div v-else-if="row.type === 'properties'" class="flex flex-wrap gap-1.5 pt-0.5">
                    <SidebarTagComponent
                        v-for="property in row.value"
                        :key="property.key"
                        :item="property"
                        :property="property"
                        :hide-x="true"
                        :class="{'ring-2 ring-amber-400 rounded-full': property.is_main}"
                    />
                </div>

                <!-- state badge -->
                <div v-else-if="row.type === 'state'" class="pt-0.5">
                    <span
                        class="rounded-full items-center font-medium px-3 py-1 text-sm inline-flex"
                        :style="getStateStyles(row.value?.color)"
                    >
                        {{ row.value?.name ?? '' }}
                    </span>
                </div>
            </div>
        </div>

        <ProjectCreateModal
            v-if="editingProject"
            :show="editingProject"
            :categories="categoriesForCreateModal"
            :genres="genresForCreateModal"
            :sectors="sectorsForCreateModal"
            :project-groups="projectGroupsForCreateModal"
            :states="headerObject.states"
            :create-settings="createSettings"
            :project="project"
            :selected-group="selectedGroupForCreateModal"
            @close-create-project-modal="closeEditProjectModal"
        />
    </div>
</template>

<script>
import {defineComponent} from 'vue';
import {can, is} from 'laravel-permission-to-vuejs';
import ProjectCreateModal from '@/Layouts/Components/ProjectCreateModal.vue';
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue';
import SidebarTagComponent from '@/Layouts/Components/SidebarTagComponent.vue';

export default defineComponent({
    name: 'ProjectBasicDataDisplayComponent',
    components: {SidebarTagComponent, PropertyIcon, ProjectCreateModal},
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
        // full option lists (for edit modal)
        categories: {
            type: Array,
            required: false,
            default: () => ([])
        },
        genres: {
            type: Array,
            required: false,
            default: () => ([])
        },
        sectors: {
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
        propertiesBadges() {
            const toBadge = (item, type) => {
                const name = item?.name;
                if (!name) {
                    return null;
                }

                return {
                    ...item,
                    key: `${type}-${item?.id ?? name}`,
                    name,
                    color: item?.color ? item.color : '#ffffff',
                    is_main: !!item?.pivot?.is_main
                };
            };

            const badges = [
                ...(this.projectCategories ?? []).map((c) => toBadge(c, 'category')),
                ...(this.projectGenres ?? []).map((g) => toBadge(g, 'genre')),
                ...(this.projectSectors ?? []).map((s) => toBadge(s, 'sector')),
            ].filter(Boolean);

            // Sort main items first
            badges.sort((a, b) => (b.is_main ? 1 : 0) - (a.is_main ? 1 : 0));

            return badges;
        },
        rows() {
            const rows = [];

            if (this.project?.name) {
                rows.push({
                    key: 'name',
                    label: this.$t('Project name'),
                    type: 'text',
                    value: this.project.name
                });
            }

            if (this.project?.artists) {
                rows.push({
                    key: 'artists',
                    label: this.$t('Artists'),
                    type: 'text',
                    value: this.project.artists
                });
            }

            if (this.propertiesBadges.length) {
                rows.push({
                    key: 'properties',
                    label: this.$t('Project properties'),
                    type: 'properties',
                    value: this.propertiesBadges
                });
            }

            if (this.project?.state?.name) {
                rows.push({
                    key: 'state',
                    label: this.$t('Project status'),
                    type: 'state',
                    value: this.project.state
                });
            }

            if (this.project?.cost_center?.name) {
                rows.push({
                    key: 'cost_center',
                    label: this.$t('Name of the cost unit'),
                    type: 'text',
                    value: this.project.cost_center.name
                });
            }

            if (this.project?.budget_deadline) {
                rows.push({
                    key: 'budget_deadline',
                    label: this.$t('Budget deadline'),
                    type: 'text',
                    value: this.formatDate(this.project.budget_deadline)
                });
            }

            return rows;
        },

        categoriesForCreateModal() {
            return (this.categories?.length ? this.categories : this.projectCategories) ?? [];
        },
        genresForCreateModal() {
            return (this.genres?.length ? this.genres : this.projectGenres) ?? [];
        },
        sectorsForCreateModal() {
            return (this.sectors?.length ? this.sectors : this.projectSectors) ?? [];
        },

        projectGroupsForCreateModal() {
            // Header-Objekte sind historisch nicht überall gleich benannt.
            return this.headerObject?.projectGroups ?? this.headerObject?.groupProjects ?? [];
        },
        selectedGroupForCreateModal() {
            return this.headerObject?.currentGroup ?? null;
        }
    },
    methods: {
        getStateStyles(color) {
            if (!color) return {};
            const hexColor = color.startsWith('#') ? color : '#' + color;
            return {
                backgroundColor: hexColor,
                color: this.getTextColorForHex(color)
            };
        },
        getTextColorForHex(color) {
            if (!color) return '#000000';
            const hexMatch = color.match(/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/);
            if (hexMatch) {
                let hex = hexMatch[1];
                if (hex.length === 3) {
                    hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
                }
                const r = parseInt(hex.substring(0, 2), 16);
                const g = parseInt(hex.substring(2, 4), 16);
                const b = parseInt(hex.substring(4, 6), 16);
                const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
                return luminance > 0.5 ? '#000000' : '#ffffff';
            }
            return '#ffffff';
        },
        formatDate(dateValue) {
            if (!dateValue) {
                return '';
            }

            // Most project dates come as `YYYY-MM-DD` from backend.
            if (typeof dateValue === 'string') {
                const m = dateValue.match(/^(\d{4})-(\d{2})-(\d{2})/);
                if (m) {
                    return `${m[3]}.${m[2]}.${m[1]}`;
                }
            }

            const parsed = new Date(dateValue);
            if (Number.isNaN(parsed.getTime())) {
                return String(dateValue);
            }

            return new Intl.DateTimeFormat('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }).format(parsed);
        },
        openEditProjectModal() {
            this.editingProject = true;
        },
        closeEditProjectModal() {
            this.editingProject = false;
        }
    }
});
</script>
