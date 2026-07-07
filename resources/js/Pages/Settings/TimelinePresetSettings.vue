<template>
    <app-layout :title="$t('Timeline Presets')">
        <EventSettingHeader>
            <div>
                <BasePageTitle
                    title="Timeline Presets"
                    description="Manage your timeline presets. You can edit or delete existing presets."
                />
            </div>

            <ul role="list" class="mt-5 space-y-2">
                <li
                    v-for="preset in timelinePresets"
                    :key="preset.id"
                    class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-5 py-4"
                >
                    <div class="flex items-center gap-x-4 flex-1 min-w-0">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-orange-100 text-orange-600 font-semibold text-sm">
                            {{ preset.times_count }}
                        </div>
                        <div class="min-w-0">
                            <p class="mDark truncate">{{ preset.name }}</p>
                            <p class="xxsLight">
                                {{ preset.times_count }} {{ $t('Points') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center ml-4">
                        <BaseMenu has-no-offset white-menu-background>
                            <BaseMenuItem
                                title="Edit"
                                :icon="IconEdit"
                                white-menu-background
                                @click="openEditModal(preset)"
                            />
                            <BaseMenuItem
                                title="Delete"
                                :icon="IconTrash"
                                white-menu-background
                                @click="openDeleteModal(preset)"
                            />
                        </BaseMenu>
                    </div>
                </li>
            </ul>

            <div v-if="timelinePresets.length === 0" class="mt-5 text-sm text-gray-500">
                {{ $t('No timeline presets found.') }}
            </div>
        </EventSettingHeader>

        <AddEditTimelineModal
            v-if="showEditModal"
            :preset="presetToEdit"
            @close="closeEditModal"
        />

        <ConfirmDeleteModal
            v-if="showDeleteModal"
            :title="$t('Delete timeline preset')"
            :description="$t('Do you really want to delete this timeline preset? This action cannot be undone.')"
            @closed="showDeleteModal = false"
            @delete="confirmDelete"
        />
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import EventSettingHeader from "@/Pages/Settings/EventSettingComponents/EventSettingHeader.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import AddEditTimelineModal from "@/Pages/Projects/Components/TimelineComponents/AddEditTimelineModal.vue";
import {IconEdit, IconTrash} from "@tabler/icons-vue";
import {router} from "@inertiajs/vue3";

export default {
    components: {
        AppLayout,
        EventSettingHeader,
        BasePageTitle,
        BaseMenu,
        BaseMenuItem,
        ConfirmDeleteModal,
        AddEditTimelineModal,
    },
    props: {
        timelinePresets: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            showEditModal: false,
            presetToEdit: null,
            showDeleteModal: false,
            presetToDelete: null,
        }
    },
    methods: {
        IconEdit,
        IconTrash,
        openEditModal(preset) {
            this.presetToEdit = preset;
            this.showEditModal = true;
        },
        closeEditModal() {
            this.showEditModal = false;
            this.presetToEdit = null;
            router.reload({ preserveScroll: true });
        },
        openDeleteModal(preset) {
            this.presetToDelete = preset;
            this.showDeleteModal = true;
        },
        confirmDelete() {
            if (!this.presetToDelete) return;
            router.delete(
                route('timeline-preset.destroy', {shiftPresetTimeline: this.presetToDelete.id}),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.showDeleteModal = false;
                        this.presetToDelete = null;
                    }
                }
            );
        }
    }
}
</script>
