<template>
    <div class="mt-10 border-t border-border-subtle pt-8">
        <div class="sm:flex sm:items-center mb-4">
            <div class="sm:flex-auto">
                <h2 class="text-lg font-semibold text-text">{{ $t('BI Tags') }}</h2>
                <p class="text-sm text-text-subtle">{{ $t('Define tags for BI counting of event types.') }}</p>
            </div>
            <div class="mt-3 sm:mt-0 sm:ml-4">
                <BaseUIButton variant="primary" hide-icon @click="openAddTagModal">
                    <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                    {{ $t('New BI tag') }}
                </BaseUIButton>
            </div>
        </div>

        <!-- Explanation of what BI tags are used for -->
        <div class="mb-6 rounded-lg border border-accent-200 bg-accent-50/60 px-4 py-3">
            <div class="flex gap-3">
                <component :is="IconInfoCircle" class="size-5 shrink-0 text-accent-600 mt-0.5" stroke-width="1.5" />
                <div class="text-sm text-accent-700 space-y-1">
                    <p>{{ $t('BI tags group event types for the BI module. For each tag, the number of event days per project is counted and shown in the BI dashboard and in BI exports (one column per tag).') }}</p>
                    <p class="text-accent-700">{{ $t('The tags "Vorstellung" and "Veranstaltungstag" additionally control the key figures performances and event days. If they have no event types assigned, all events of a project are counted there as a fallback.') }}</p>
                </div>
            </div>
        </div>

        <!-- Warning: tags without linked event types -->
        <div v-if="tagsWithoutEventTypes.length > 0" class="mb-6 rounded-lg border border-warning-border bg-warning-surface px-4 py-3 text-sm text-warning">
            {{ $t('The following tags are not linked to any event type yet, so they will not be counted in the dashboard and exports:') }}
            <button
                v-for="tag in tagsWithoutEventTypes"
                :key="tag.id"
                type="button"
                class="ml-1.5 inline-flex items-center gap-1 rounded-full border border-warning-border bg-white px-2.5 py-0.5 text-xs font-medium text-warning hover:bg-warning-surface"
                @click="openAssignModal(tag)"
            >
                <span v-if="tag.color" class="w-2 h-2 rounded-full" :style="{ backgroundColor: tag.color }" />
                {{ tag.name_de }}
                <component :is="IconCirclePlus" class="size-3.5" stroke-width="1.5" />
            </button>
        </div>

        <!-- Tags list -->
        <div class="space-y-3" v-if="tags.length > 0">
            <div v-for="tag in tags" :key="tag.id" class="border border-border-subtle rounded-lg p-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-4 h-4 rounded-full shrink-0" :style="{ backgroundColor: tag.color ?? '#d1d5db' }" />
                        <span class="font-medium text-sm truncate">{{ tag.name_de }}</span>
                        <span class="text-xs text-text-subtle shrink-0">({{ tag.name }})</span>
                        <span
                            v-if="kpiLabel(tag)"
                            class="hidden sm:inline-flex items-center rounded-full bg-accent-100 px-2.5 py-0.5 text-xs font-medium text-accent-700 shrink-0"
                        >
                            {{ $t('Controls the key figure {kpi}', { kpi: kpiLabel(tag) }) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <BaseMenu has-no-offset white-menu-background>
                            <BaseMenuItem title="Assign event types" :icon="IconLink" white-menu-background @click="openAssignModal(tag)" />
                            <BaseMenuItem title="Edit BI tag" :icon="IconEdit" white-menu-background @click="openEditTagModal(tag)" />
                            <BaseMenuItem title="Delete BI tag" :icon="IconTrash" white-menu-background @click="openDeleteModal(tag)" />
                        </BaseMenu>
                    </div>
                </div>

                <!-- Assigned event types as chips -->
                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    <span
                        v-for="eventType in tag.event_types ?? []"
                        :key="eventType.id"
                        class="group inline-flex items-center gap-1.5 rounded-full border border-border-subtle bg-surface-sunken pl-2 pr-1 py-0.5 text-xs text-text-muted"
                    >
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: eventType.hex_code }" />
                        {{ eventType.name }}
                        <button
                            type="button"
                            class="rounded-full p-0.5 text-text-subtle hover:bg-border-subtle hover:text-text-muted"
                            :title="$t('Remove')"
                            @click="removeEventType(tag, eventType)"
                        >
                            <component :is="IconX" class="size-3" stroke-width="1.5" />
                        </button>
                    </span>
                    <span v-if="!(tag.event_types?.length)" class="text-xs text-text-subtle italic">
                        {{ $t('No event types assigned yet') }}
                    </span>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-dashed border-border px-2 py-0.5 text-xs text-text-subtle hover:border-border-strong hover:text-text-muted"
                        @click="openAssignModal(tag)"
                    >
                        <component :is="IconCirclePlus" class="size-3.5" stroke-width="1.5" />
                        {{ $t('Assign event types') }}
                    </button>
                </div>
            </div>
        </div>
        <p v-else class="text-sm text-text-subtle">{{ $t('No BI tags defined yet.') }}</p>

        <!-- Event types not covered by any tag -->
        <div v-if="tags.length > 0 && unassignedEventTypes.length > 0" class="mt-4 flex flex-wrap items-center gap-1.5 text-xs text-text-subtle">
            <span>{{ $t('Event types without a BI tag') }}:</span>
            <span
                v-for="eventType in unassignedEventTypes"
                :key="eventType.id"
                class="inline-flex items-center gap-1.5 rounded-full border border-border-subtle px-2 py-0.5"
            >
                <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: eventType.hex_code }" />
                {{ eventType.name }}
            </span>
        </div>

        <AddEditBiTagModal
            v-if="showTagModal"
            :tag="tagToEdit"
            @close="closeTagModal"
        />
        <AssignBiTagEventTypesModal
            v-if="tagToAssign"
            :tag="tagToAssign"
            :event-types="eventTypes"
            @close="closeAssignModal"
        />
        <DeleteBiTagConfirmationModal
            v-if="tagToDelete"
            :tag="tagToDelete"
            @closed="tagToDelete = null"
            @delete="deleteTag"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { IconCirclePlus, IconEdit, IconInfoCircle, IconLink, IconTrash, IconX } from '@tabler/icons-vue';
import BaseMenu from '@/Components/Menu/BaseMenu.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue';
import AddEditBiTagModal from '@/Pages/Settings/EventType/Components/Modals/AddEditBiTagModal.vue';
import AssignBiTagEventTypesModal from '@/Pages/Settings/EventType/Components/Modals/AssignBiTagEventTypesModal.vue';
import DeleteBiTagConfirmationModal from '@/Pages/Settings/EventType/Components/Modals/DeleteBiTagConfirmationModal.vue';
import { useTranslation } from '@/Composeables/Translation.js';

const $t = useTranslation();

const props = defineProps({
    eventTypes: { type: Array, default: () => [] },
});

const tags = ref([]);

const showTagModal = ref(false);
const tagToEdit = ref(null);
const tagToAssign = ref(null);
const tagToDelete = ref(null);

const tagsWithoutEventTypes = computed(() => tags.value.filter((tag) => !(tag.event_types && tag.event_types.length)));

const unassignedEventTypes = computed(() => {
    const assignedIds = new Set(tags.value.flatMap((tag) => (tag.event_types ?? []).map((eventType) => eventType.id)));
    return props.eventTypes.filter((eventType) => !assignedIds.has(eventType.id));
});

// Spiegelt die Backend-Heuristik (BiProjectMetricsService::eventHasTag): Vergleich
// case-insensitive auf name UND name_de gegen die deutschen KPI-Tag-Namen.
const kpiLabel = (tag) => {
    const names = [tag.name, tag.name_de].map((name) => (name ?? '').toLowerCase());
    if (names.includes('vorstellung')) return $t('Performances');
    if (names.includes('veranstaltungstag')) return $t('Event days');
    return null;
};

const fetchTags = async () => {
    try {
        const response = await axios.get(route('bi.tags.index'));
        tags.value = response.data;
    } catch (error) {
        console.error('Error loading BI tags', error);
    }
};

const openAddTagModal = () => {
    tagToEdit.value = null;
    showTagModal.value = true;
};

const openEditTagModal = (tag) => {
    tagToEdit.value = tag;
    showTagModal.value = true;
};

const closeTagModal = async (saved) => {
    showTagModal.value = false;
    tagToEdit.value = null;
    if (saved) await fetchTags();
};

const openAssignModal = (tag) => {
    tagToAssign.value = tag;
};

const closeAssignModal = async (saved) => {
    tagToAssign.value = null;
    if (saved) await fetchTags();
};

const openDeleteModal = (tag) => {
    tagToDelete.value = tag;
};

const deleteTag = async () => {
    try {
        await axios.delete(route('bi.tags.destroy', tagToDelete.value.id));
        tagToDelete.value = null;
        await fetchTags();
    } catch (error) {
        console.error('Error deleting tag', error);
    }
};

const removeEventType = async (tag, eventType) => {
    try {
        const remainingIds = (tag.event_types ?? [])
            .filter((assigned) => assigned.id !== eventType.id)
            .map((assigned) => assigned.id);
        await axios.post(route('bi.tags.sync-event-types', tag.id), { event_type_ids: remainingIds });
        await fetchTags();
    } catch (error) {
        console.error('Error removing event type from tag', error);
    }
};

onMounted(fetchTags);
</script>
