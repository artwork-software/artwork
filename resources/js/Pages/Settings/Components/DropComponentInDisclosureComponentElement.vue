<template>
    <div
        @dragleave="dropOver = false"
        @dragover="onDragOver"
        @drop="onDrop"
        :class="[ 'flex items-center h-4 min-h-4 rounded transition',
            isDragging ? 'border-2 border-dashed' : '',
            isDragging && invalidDropReason
                ? 'border-danger-border bg-danger-surface/40 opacity-70 cursor-not-allowed'
                : (isDragging && dropOver
                    ? 'border-success-border bg-success-surface/60 ring-2 ring-success-border/30 cursor-pointer'
                    : (isDragging ? 'border-border bg-surface-sunken/40 cursor-pointer' : 'cursor-pointer'))
        ]"
        :aria-hidden="!isDragging"
        :aria-dropeffect="isDragging && !invalidDropReason ? 'copy' : undefined"
    >
        <div v-if="isDragging && invalidDropReason" class="h-full w-full flex items-center justify-center gap-2 text-[11px] pointer-events-none">
            <span class="font-medium text-danger">{{ $t(invalidDropReason) }}</span>
        </div>
        <div v-else-if="isDragging" class="h-full w-full flex items-center justify-center gap-2 text-[11px] text-text-muted pointer-events-none">
            <span class="font-medium" :class="dropOver ? 'text-success' : ''">{{ $t('Drop into folder') }}</span>
        </div>
        <span v-else-if="dropOver" class="text-xs text-text-subtle w-full flex items-center justify-center pointer-events-none">
            {{ $t('Release to add') }}
        </span>
    </div>
    <SelectTabsModalForDisclosure
        v-if="showSelectTabsModal"
        :tabs="allTabs"
        :current-tab="currentTab"
        :position="index"
        :component-data="componentData"
        :disclosure-id="element.component.id"
        @close="showSelectTabsModal = false"
    />
</template>

<script setup>

import {computed, onMounted, onUnmounted, ref} from "vue";
import {router} from "@inertiajs/vue3";
import { EventListenerForDragging } from "@/Composeables/EventListenerForDragging.js";
import SelectTabsModalForDisclosure from "@/Pages/Settings/Components/SelectTabsModalForDisclosure.vue";

const props = defineProps({
    element: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
    allTabs: {
        type: Array,
        required: false,
        default: () => []
    },
    currentTab: {
        type: Object,
        required: false,
        default: null
    }
})

const dropOver = ref(false);
const showSelectTabsModal = ref(false);
const componentData = ref(null);
const { isDragging, draggedComponent, addEventListenerForDraggingStart, removeEventListenerForDraggingStart } = EventListenerForDragging();
let listeners = null;

// Große Layout-Komponenten blockieren – diese funktionieren nicht in Ordnern
const blockedInDisclosure = [
    'CalendarTab',
    'ShiftTab',
    'BudgetTab',
    'BulkBody',
    'ChecklistAllComponent',
    'CommentAllTab',
    'ProjectAllDocumentsComponent',
];

// Übersetzungskey des Ablage-Verbots für die aktuell gezogene Komponente (null = erlaubt)
const invalidDropReason = computed(() => {
    const data = draggedComponent.value;
    if (!data) return null;
    if (data.type === 'DisclosureComponent') {
        return 'Folders cannot be nested inside folders';
    }
    if (blockedInDisclosure.includes(data.type)) {
        return 'This component cannot be placed inside a folder';
    }
    return null;
});

onMounted(() => {
    listeners = addEventListenerForDraggingStart();
});

onUnmounted(() => {
    removeEventListenerForDraggingStart(listeners);
});

const onDragOver = (event) => {
    dropOver.value = true;
    event.preventDefault();
}

const onDrop = (event) => {
    event.preventDefault();

    // add check if JSON is valid
    if (!event.dataTransfer?.getData('application/json')) {
        dropOver.value = false;
        return false;
    }

    const data = JSON?.parse(event.dataTransfer?.getData('application/json'));

    if(data.drop_type !== 'component') {
        dropOver.value = false;
        return false;
    }

    if(data.type === 'DisclosureComponent') {
        dropOver.value = false;
        return false;
    }

    if(blockedInDisclosure.includes(data.type)) {
        dropOver.value = false;
        return false;
    }

    // Check if component requires scope selection (CommentTab, ChecklistComponent, or ProjectDocumentsComponent)
    if(data.type === 'ProjectDocumentsComponent' || data.type === 'CommentTab' || data.type === 'ChecklistComponent') {
        componentData.value = data;
        showSelectTabsModal.value = true;
        dropOver.value = false;
        return;
    }

    router.post(route('project-management-builder.add.disclosure.component'), {
        order: props.index,
        component_id: data.id,
        disclosure_id: props.element.component.id
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            dropOver.value = false;
        }
    });
}

</script>

<style scoped>

</style>
