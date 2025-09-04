<template>
    <tr :key="artist_residency.id">
        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">{{ artist_residency.artist.name }}</td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ artist_residency.artist.position }}</td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ artist_residency.artist.phone_number }}</td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ artist_residency.formatted_dates.arrival_date }} {{ artist_residency.formatted_dates.arrival_time }}</td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ artist_residency.formatted_dates.departure_date }} {{ artist_residency.formatted_dates.departure_time }}</td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ artist_residency.accommodation?.name ?? $t('Deleted') }}</td>
        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
            <BaseMenu dots-size="h-5 w-5" has-no-offset white-menu-background>
                <BaseMenuItem white-menu-background icon="IconEdit" title="Edit" @click="$emit('editResidency', artist_residency)"/>
                <BaseMenuItem white-menu-background icon="IconCopy" title="Duplicate" @click="duplicate"/>
                <BaseMenuItem white-menu-background icon="IconTrash" title="Delete" @click="deleteArtistResidency"/>
            </BaseMenu>
        </td>
    </tr>


    <ConfirmDeleteModal
        v-if="showDeleteConfirmation"
        @close="showDeleteConfirmation = false"
        @delete="sendDelete"
        :title="$t('Delete artist residency')"
        :description="$t('Are you sure you want to delete this artist residency?')"
        />
</template>

<script setup>

import {router, usePage} from "@inertiajs/vue3";
import {MenuItem} from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import AddEditArtistResidenciesModal
    from "@/Pages/Projects/Components/ArtistResidenciesComponents/AddEditArtistResidenciesModal.vue";
import {ref} from "vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

const props = defineProps({
    artist_residency: {
        type: Object,
        required: true
    },
    project: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['editResidency']);

const showAddEditArtistResidenciesModal = ref(false);


const duplicate = () => {
    router.post(route('artist_residencies.duplicate', {artistResidency: props.artist_residency.id}), {
    });
}

const showDeleteConfirmation = ref(false);

const deleteArtistResidency = () => {
    showDeleteConfirmation.value = true;
}

const sendDelete = () => {
    router.delete(route('artist-residency.destroy', {artistResidency: props.artist_residency.id}), {
    });
}
</script>

<style scoped>

</style>
