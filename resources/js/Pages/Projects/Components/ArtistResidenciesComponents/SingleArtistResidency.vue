<template>
    <tr :key="artist_residency.id">
        <td v-for="(column, columnIndex) in nameColumns" :key="column.key"
            :class="columnIndex === 0
                ? 'whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-text sm:pl-0'
                : 'whitespace-nowrap px-3 py-4 text-sm text-text-subtle'">
            <BaseInput
                v-if="editingKey === column.key"
                :ref="setEditInputRef"
                v-model="editableValue"
                :id="`artist-residency-${artist_residency.id}-${column.key}`"
                :label="columnLabel(column.key)"
                :show-label="false"
                is-small
                class="w-44"
                @focusout="saveEdit"
                @keydown.enter.prevent="$event.target.blur()"
                @keydown.esc.prevent="cancelEdit"
            />
            <button
                v-else
                type="button"
                class="group inline-flex items-center gap-1 rounded px-1 -mx-1 text-left hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-700"
                :title="$t('Rename')"
                @click="startEditing(column.key)"
            >
                <span>{{ columnValue(column.key) || '–' }}</span>
                <component
                    :is="IconEdit"
                    class="h-3.5 w-3.5 shrink-0 text-text-subtle opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100"
                />
            </button>
        </td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ artist_residency?.position || artist_residency?.artist?.position || '' }}</td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ artist_residency?.phone_number || artist_residency?.artist?.phone_number || '' }}</td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ artist_residency.formatted_dates.arrival_date }} {{ artist_residency.formatted_dates.arrival_time }}</td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ artist_residency.formatted_dates.departure_date }} {{ artist_residency.formatted_dates.departure_time }}</td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ artist_residency.accommodation?.name ?? $t('Deleted') }}</td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ artist_residency.room_type?.name ?? '-' }}</td>
        <td class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ calculateTotalCost(artist_residency) }} €</td>
        <td class="px-3 py-4 text-sm text-text-subtle max-w-[200px]">
            <span class="block truncate" v-tooltip.bottom="{ value: artist_residency.description, class: 'aw-tooltip' }">{{ artist_residency.description || '-' }}</span>
        </td>
        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
            <BaseMenu dots-size="h-5 w-5" has-no-offset white-menu-background>
                <BaseMenuItem white-menu-background :icon="IconEdit" title="Edit" @click="$emit('editResidency', artist_residency)"/>
                <BaseMenuItem white-menu-background :icon="IconCopy" title="Duplicate" @click="duplicate"/>
                <BaseMenuItem white-menu-background :icon="IconTrash" title="Delete" @click="deleteArtistResidency"/>
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
import {nextTick, ref} from "vue";
import axios from "axios";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {IconCopy, IconEdit, IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    artist_residency: {
        type: Object,
        required: true
    },
    project: {
        type: Object,
        required: true
    },
    nameColumns: {
        type: Array,
        required: false,
        default: () => [{ key: 'name', enabled: true }]
    }
})

const emit = defineEmits(['editResidency', 'deleted', 'duplicated']);

const columnValue = (key) => {
    if (key === 'first_name') {
        return props.artist_residency?.resolved_first_name ?? '';
    }
    if (key === 'last_name') {
        return props.artist_residency?.resolved_last_name ?? '';
    }
    return props.artist_residency?.resolved_name ?? props.artist_residency?.display_name ?? '';
};

const showAddEditArtistResidenciesModal = ref(false);

const columnLabel = (key) => ({
    name: 'Artist name',
    first_name: 'First name',
    last_name: 'Last name',
}[key] ?? key);

/**
 * Inline-Umbenennung direkt in der Tabelle (Ticket „Anpassungen HAU" Punkt 13):
 * Klick auf den Namen öffnet ein Eingabefeld, Enter/Blur speichert, Escape verwirft.
 * Gilt für jede konfigurierte Namensspalte und jeden Aufenthalt – der Wert landet
 * ausschließlich in den lokalen Spalten des Aufenthalts, nie am CRM-Kontakt.
 */
const editingKey = ref(null);
const editableValue = ref('');
const editInputRef = ref(null);
const setEditInputRef = (el) => {
    if (el) {
        editInputRef.value = el;
    }
};

const startEditing = (key) => {
    editingKey.value = key;
    editableValue.value = columnValue(key);
    nextTick(() => {
        editInputRef.value?.focus?.();
        editInputRef.value?.select?.();
    });
};

const cancelEdit = () => {
    editingKey.value = null;
};

const saveEdit = async () => {
    const key = editingKey.value;
    if (!key) {
        return;
    }
    editingKey.value = null;

    const trimmed = editableValue.value.trim();
    if (trimmed === columnValue(key) || (key === 'name' && !trimmed)) {
        return;
    }

    try {
        await axios.patch(
            route('artist-residencies.update-name', { artistResidency: props.artist_residency.id }),
            { field: key, value: trimmed }
        );
        props.artist_residency[key] = trimmed;
        props.artist_residency[`resolved_${key}`] = trimmed;
        if (key === 'name') {
            props.artist_residency.display_name = trimmed;
        }
    } catch (e) {
        console.error(e);
    }
};


const duplicate = () => {
    router.post(route('artist_residencies.duplicate', {artistResidency: props.artist_residency.id}), {}, {
        onSuccess: () => {
            emit('duplicated');
        }
    });
}

const showDeleteConfirmation = ref(false);

const deleteArtistResidency = () => {
    showDeleteConfirmation.value = true;
}

const sendDelete = () => {
    router.delete(route('artist-residency.destroy', {artistResidency: props.artist_residency.id}), {
        onSuccess: () => {
            showDeleteConfirmation.value = false;
            emit('deleted');
        }
    });
}

const calculateTotalCost = (artist_residency) => {
    const accommodationCost = artist_residency.cost_per_night * artist_residency.days;
    const dailyAllowanceTotal = artist_residency.daily_allowance * (artist_residency.days + Math.floor(artist_residency.additional_daily_allowance));
    const breakfastDeduction = (artist_residency.breakfast_count || 0) * (artist_residency.breakfast_deduction_per_day || 0);
    const payoutPerDiem = dailyAllowanceTotal - breakfastDeduction;
    const totalCost = accommodationCost + payoutPerDiem;
    return totalCost.toFixed(2);
}
</script>

<style scoped>

</style>
