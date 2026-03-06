<template>
    <div>
        <div class="flex items-center gap-x-5">
            <h3 class="headline3">{{ $t('Project status') }}</h3>
            <PropertyIcon
                name="IconEdit"
                class="w-5 h-5 rounded-full text-artwork-buttons-context cursor-pointer"
                @click="openEditModal"
            />
        </div>
        <div v-if="loadError" class="text-xs text-rose-600">
            {{ loadError }}
        </div>
        <div v-else-if="loading" class="text-xs text-secondary">
            {{ $t('Loading data...') }}
        </div>
        <span v-else-if="projectState" class="rounded-full items-center font-medium px-3 py-1 my-2 text-sm ml-2 mb-1 inline-flex"
              :class="isHexColor(projectState?.color) ? '' : (projectState?.color ?? '')"
              :style="getStyles(projectState?.color)">
            {{ projectState?.name ?? '' }}
        </span>

        <ArtworkBaseModal
            v-if="showEditModal"
            :title="$t('Change project status')"
            :description="$t('Select a new status for this project.')"
            @close="showEditModal = false"
        >
            <div class="px-6 pb-6">
                <!-- Show tag when state is selected -->
                <div v-if="selectedState" class="w-full">
                    <div class="text-gray-500 text-xs mb-2">
                        {{ $t('Project status') }}
                    </div>
                    <div class="inline-flex items-center gap-x-2 px-3 py-1.5 rounded-full border border-gray-300 bg-white">
                        <div class="block w-3 h-3 rounded-full" :style="{'backgroundColor' : selectedState.color }"/>
                        <span class="text-sm flex items-center gap-x-1">
                            {{ selectedState.name }}
                            <IconCalendarMonth v-if="selectedState.is_planning === true || selectedState.is_planning === 1" class="w-4 h-4" />
                        </span>
                        <button type="button" @click="selectedState = null" class="ml-1">
                            <IconCircleX class="h-4 w-4 text-gray-400 hover:text-error" />
                        </button>
                    </div>
                </div>
                <!-- Show dropdown when no state is selected -->
                <Listbox v-else as="div" class="w-full relative" v-model="selectedState">
                    <ListboxButton class="menu-button-no-padding relative">
                        <div class="truncate">
                            <div class="top-2 left-4 absolute text-gray-500 text-xs">
                                {{ $t('Project status') }}
                            </div>
                            <div class="pt-6 pb-2 flex items-center gap-x-2">
                                <div class="truncate">
                                    <span>{{ $t('Select project status') }}</span>
                                </div>
                            </div>
                        </div>
                        <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                    </ListboxButton>
                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <ListboxOptions class="absolute w-full z-10 bg-primary shadow-lg max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                            <ListboxOption as="template" class="max-h-8"
                                v-for="state in availableStates"
                                :key="state.id"
                                :value="state"
                                v-slot="{ active, selected }">
                                <li :class="[active ? 'text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="block w-3 h-3 rounded-full" :style="{'backgroundColor' : state?.color }"/>
                                        </div>
                                        <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 flex items-center gap-x-1 truncate']">
                                            {{ state.name }}
                                            <IconCalendarMonth v-if="state.is_planning === true || state.is_planning === 1" class="w-4 h-4" />
                                        </span>
                                    </div>
                                    <span :class="[active ? 'text-white' : 'text-secondary', 'group flex justify-end items-center text-sm subpixel-antialiased']">
                                        <CheckIcon v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                    </span>
                                </li>
                            </ListboxOption>
                        </ListboxOptions>
                    </transition>
                </Listbox>

                <div class="flex justify-end mt-6">
                    <button
                        type="button"
                        class="bg-artwork-buttons-create text-white rounded-full px-6 py-2 text-sm font-semibold hover:bg-artwork-buttons-hover"
                        @click="saveState"
                    >
                        {{ $t('Save') }}
                    </button>
                </div>
            </div>
        </ArtworkBaseModal>
    </div>
</template>
<script>
import {defineComponent} from "vue";
import axios from "axios";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {CheckIcon, ChevronDownIcon} from "@heroicons/vue/solid";
import {IconCalendarMonth, IconCircleX} from "@tabler/icons-vue";
import {router, usePage} from "@inertiajs/vue3";
import {can} from "laravel-permission-to-vuejs";

export default defineComponent({
    components: {
        PropertyIcon,
        ArtworkBaseModal,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        CheckIcon,
        ChevronDownIcon,
        IconCalendarMonth,
        IconCircleX,
    },
    props: [
        'project',
        'projectId',
        'loadedProjectInformation',
        'canEditComponent',
        'headerObject',
    ],
    data() {
        return {
            loading: false,
            loadError: null,
            projectState: this.project?.state ?? this.loadedProjectInformation?.['ProjectStateComponent'] ?? null,
            showEditModal: false,
            selectedState: null,
        };
    },
    computed: {
        canEdit() {
            return this.canEditComponent && this.projectMembersWriteAccess();
        },
        availableStates() {
            return this.headerObject?.states ?? [];
        },
    },
    mounted() {
        this.ensureStatusData();
    },
    methods: {
        projectMembersWriteAccess() {
            if (can('write projects')) {
                return true;
            }
            const writeAuth = this.project?.write_auth ?? [];
            if (writeAuth.length === 0) {
                return false;
            }
            return writeAuth.map(w => w.id).includes(usePage().props.auth.user.id);
        },
        openEditModal() {
            this.selectedState = this.projectState
                ? this.availableStates.find(s => s.id === this.projectState.id) ?? null
                : null;
            this.showEditModal = true;
        },
        saveState() {
            const projectId = this.project?.id ?? this.projectId;
            router.patch(route('update.project.state', {project: projectId}), {
                state: this.selectedState ? this.selectedState.id : null,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    this.projectState = this.selectedState ? {...this.selectedState} : null;
                    this.showEditModal = false;
                },
            });
        },
        isHexColor(color) {
            if (!color) return false;
            return /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/.test(color);
        },
        getStyles(color) {
            if (!color) return {};
            if (this.isHexColor(color)) {
                const hexColor = color.startsWith('#') ? color : '#' + color;
                return {
                    backgroundColor: hexColor,
                    color: this.getTextColor(color)
                };
            }
            return { color: this.getTextColor(color) };
        },
        getTextColor(color) {
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
            const lightColors = [
                'bg-white', 'bg-gray-50', 'bg-gray-100', 'bg-gray-200', 'bg-gray-300',
                'bg-yellow-50', 'bg-yellow-100', 'bg-yellow-200', 'bg-yellow-300', 'bg-yellow-400',
                'bg-lime-50', 'bg-lime-100', 'bg-lime-200', 'bg-lime-300', 'bg-lime-400'
            ];
            const isLight = lightColors.some(light => color.includes(light));
            return isLight ? '#000000' : '#ffffff';
        },
        async ensureStatusData() {
            if (this.projectState) {
                return;
            }

            const id = this.project?.id ?? this.projectId;
            if (!id) {
                return;
            }

            this.loading = true;
            this.loadError = null;

            try {
                const response = await axios.get(route('projects.tabs.status', {project: id}));
                this.projectState = response.data.state ?? null;
            } catch (e) {
                this.loadError = this.$t
                    ? this.$t('Status konnte nicht geladen werden.')
                    : 'Failed to load status.';
            } finally {
                this.loading = false;
            }
        }
    }
});
</script>
