<template>
    <div class="print:break-before-auto">
        <div class="sm:flex sm:items-center ">
            <div class="sm:flex-auto">
                <TinyPageHeadline :title="$t('artist management')" :description="$t('Manage the artist management for this project.')"/>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex items-center gap-x-4 print:hidden">
                <ToolTipComponent
                    icon="IconFileExport"
                    :tooltip-text="$t('Export artist management')"
                    direction="bottom"
                    @click="openExportArtistResidenciesModal = true"
                />
                <ArtworkBaseModalButton @click="showAddEditArtistResidenciesModal = true" variant="primary">
                    {{ $t('Add artist residency') }}
                </ArtworkBaseModalButton>
            </div>
        </div>
        <div class="max-w-lg">
            <VisualFeedback
                :show-save-success="showSaveSuccess"
                text="Saved. The Artist Residency has been successfully applied."
            />
        </div>
        <div class="mt-8 flow-root border-b border-dashed border-gray-400">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">{{ $t('Name artist')}}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Position') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('phone number') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Arrival date') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Date departure') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Accommodation') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Room type') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Total cost') }}</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <SingleArtistResidency :project="project" v-for="artist_residency in usePage().props.artist_residencies" :artist_residency="artist_residency" @edit-residency="editResidency" :key="artist_residency.id"/>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end mt-3 gap-x-3">
            <div class="flex items-center gap-x-1">
                <component :is="IconBuildingSkyscraper" class="h-4 w-4"/>
                <span class="text-xs">{{ $t('Costs for overnight stays') }}: <span class="underline decoration-double decoration-slate-300 underline-offset-2">{{ totalCostOfArtistResidencies }} €</span></span>
            </div>
            <div class="flex items-center gap-x-1">
                <component :is="IconMoneybag" class="h-4 w-4"/>
                <span class="text-xs">{{ $t('Costs of daily allowances') }}: <span class="underline decoration-double decoration-slate-300 underline-offset-2">{{ totalAllowanceOfArtistResidencies }} €</span></span>
            </div>
        </div>
    </div>

    <AddEditArtistResidenciesModal
        v-if="showAddEditArtistResidenciesModal"
        @close="closeAddEditArtistResidenciesModal"
        :project="project"
        :artist_residency="artistResidencyToEdit"
    />

    <ExportArtistResidenciesModal
        :project="project"
        v-if="openExportArtistResidenciesModal"
        @close="openExportArtistResidenciesModal = false"
    />

</template>

<script setup>

import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {IconBuildingSkyscraper, IconEdit, IconMoneybag} from "@tabler/icons-vue";
import {MenuItem} from "@headlessui/vue";
import AddEditArtistResidenciesModal
    from "@/Pages/Projects/Components/ArtistResidenciesComponents/AddEditArtistResidenciesModal.vue";
import {computed, ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import SingleArtistResidency from "@/Pages/Projects/Components/ArtistResidenciesComponents/SingleArtistResidency.vue";
import VisualFeedback from "@/Components/Feedback/VisualFeedback.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import ExportArtistResidenciesModal
    from "@/Pages/Projects/Components/ArtistResidenciesComponents/ExportArtistResidenciesModal.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

const props = defineProps({
    loadedProjectInformation: {
        type: Object,
        required: true
    },
    project: {
        type: Object,
        required: true
    },
    inSidebar: {
        type: Boolean,
        required: false,
        default: false
    }
})

const showAddEditArtistResidenciesModal = ref(false);
const showSaveSuccess = ref(false);
const artistResidencyToEdit = ref(null);
const showDeleteConfirmation = ref(false);
const openExportArtistResidenciesModal = ref(false);

const closeAddEditArtistResidenciesModal = (boolean) => {
    showAddEditArtistResidenciesModal.value = false;

    artistResidencyToEdit.value = null;
    if (boolean) {
        console.log('Saved');
        showSaveSuccess.value = true;
        setTimeout(() => {
            showSaveSuccess.value = false;
        }, 3000);
    }
}

const editResidency = (artist_residency) => {
    artistResidencyToEdit.value = artist_residency;
    showAddEditArtistResidenciesModal.value = true;
}

const totalCostOfArtistResidencies = computed(() => {
    // foreach artist_residency in artist_residencies calculate cost_per_night * days
    let totalCost = 0;
    usePage().props.artist_residencies.forEach((artist_residency) => {
        totalCost += artist_residency.cost_per_night * artist_residency.days;
    });
    return totalCost.toFixed(2);
})

const totalAllowanceOfArtistResidencies = computed(() => {
    // foreach artist_residency in artist_residencies calculate allowance_per_night * days
    let totalAllowance = 0;
    usePage().props.artist_residencies.forEach((artist_residency) => {
        totalAllowance += (artist_residency.daily_allowance * artist_residency.days) + artist_residency.additional_daily_allowance;
    });
    return totalAllowance.toFixed(2);
})
</script>

<style scoped>

</style>
