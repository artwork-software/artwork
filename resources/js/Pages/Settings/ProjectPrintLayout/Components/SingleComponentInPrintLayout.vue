<template>
    <div class="relative p-5 rounded-lg border-2 h-full cursor-grab active:cursor-grabbing flex items-center justify-center duration-200 ease-in-out group"
         :class="[dropOver ? 'border-accent-600 bg-accent-600/10' : 'border-border hover:border-accent-600']"
         draggable="true"
         @dragstart="onDragStart"
         @dragend="onDragEnd"
         @dragover="onDragOver"
         @dragleave="onDragLeave"
         @drop="onDrop">
        <div class="absolute bg-accent-600/40 inset-0 rounded-md hidden group-hover:block">
            <div class="flex items-center justify-center gap-x-4 h-full">
                <div class="rounded-full p-1 bg-danger shadow-md" @click="showDeleteModal = true">
                    <component :is="IconX" class="size-5 text-white" />
                </div>
            </div>
        </div>
        <div class="flex flex-col items-center justify-between pointer-events-none">
            <ComponentIcons :type="component.component.type" />
            <div>{{ $t(component.component.name) }}</div>
        </div>
    </div>

    <ConfirmDeleteModal
        v-if="showDeleteModal"
        @closed="showDeleteModal = false"
        @delete="deleteComponent"
        :title="$t('Delete component')"
        :description="$t('Are you sure you want to delete this component?')"
    />

    <SideNotification v-if="showError" type="error" :text="dropFeedback" @close="showError = false" />
</template>

<script setup>

import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {ref, watch} from "vue";
import {router} from "@inertiajs/vue3";
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import SideNotification from "@/Layouts/Components/General/SideNotification.vue";
import {EventListenerForDragging} from "@/Composeables/EventListenerForDragging.js";
import {IconX} from "@tabler/icons-vue";

const props = defineProps({
    component: {
        type: Object,
        required: true,
    },
    layout: {
        type: Object,
        required: true,
    }
})

const { dispatchEventStart, dispatchEventEnd } = EventListenerForDragging();

const showDeleteModal = ref(false);
const showError = ref(false);
const dropFeedback = ref('');
const dropOver = ref(false);

const deleteComponent = () => {
    showDeleteModal.value = false;
    router.delete(route('project-print-layout.components.destroy', {printLayoutComponent: props.component.id}));
}

const onDragStart = (event) => {
    const payload = {
        drop_type: 'placed_component',
        id: props.component.id,
        sidebar_enabled: props.component.component.sidebar_enabled,
        origin_type: props.component.type,
    };
    try {
        const json = JSON.stringify(payload);
        event.dataTransfer?.setData('application/json', json);
        event.dataTransfer?.setData('text/plain', json);
        event.dataTransfer.effectAllowed = 'move';
    } catch (e) {
        // no-op
    }
    dispatchEventStart(payload);
}

const onDragEnd = () => {
    dispatchEventEnd();
}

const onDragOver = (event) => {
    event.preventDefault();
    dropOver.value = true;
}

const onDragLeave = () => {
    dropOver.value = false;
}

const columnSizeFor = (sectionType) => props.layout['columns_' + sectionType] ?? 1;

// Gleiche Regeln wie beim Hinzufügen aus der Palette (EmptyProjectPrintComponentDropElement)
const isAllowedIn = (sidebarEnabled, sectionType) => {
    if ((sectionType === 'header' || sectionType === 'footer') && !sidebarEnabled) {
        return false;
    }
    return !(sectionType === 'body' && columnSizeFor('body') > 1 && !sidebarEnabled);
}

const onDrop = (event) => {
    event.preventDefault();
    dropOver.value = false;

    if (!event.dataTransfer?.getData('application/json')) {
        return;
    }

    const data = JSON.parse(event.dataTransfer.getData('application/json'));

    // Nur bereits platzierte Komponenten tauschen — Palette-Komponenten gehören in leere Zellen
    if (data.drop_type !== 'placed_component' || data.id === props.component.id) {
        return;
    }

    if (!isAllowedIn(data.sidebar_enabled, props.component.type)) {
        dropFeedback.value = props.component.type === 'body'
            ? "Fehler: Im Hauptteil-Bereich mit einer Spaltenanzahl größer als 1 sind keine speziellen Komponenten erlaubt."
            : "Fehler: Im \"Kopf\"- oder \"Fuß\"-Bereich sind keine speziellen Komponenten erlaubt.";
        showError.value = true;
        return;
    }

    // Beim Tausch muss auch die hiesige Komponente an der Ursprungszelle erlaubt sein
    if (data.origin_type && !isAllowedIn(props.component.component.sidebar_enabled, data.origin_type)) {
        dropFeedback.value = "Fehler: Die Komponenten können nicht getauscht werden, da eine der beiden am Zielort nicht erlaubt ist.";
        showError.value = true;
        return;
    }

    router.patch(route('project-print-layout.components.move', {printLayoutComponent: data.id}), {
        type: props.component.type,
        row: props.component.row,
        col: props.component.position,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

watch(showError, (value) => {
    if (value) {
        setTimeout(() => {
            showError.value = false;
        }, 2000);
    }
});
</script>

<style scoped>

</style>
