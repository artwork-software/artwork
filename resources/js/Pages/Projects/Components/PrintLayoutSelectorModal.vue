<template>
    <ArtworkBaseModal
        title="Select print layout"
        description="Select a print layout to print the project"
        modal-size="sm:max-w-lg"
        @close="emit('close')"
    >
        <ArtworkBaseListbox
            v-if="activePrintLayouts.length > 0"
            v-model="selectedLayout"
            :items="activePrintLayouts"
            by="id"
            option-label="name"
            label="Select print layout"
        />
        <EmptyState
            v-else
            icon="IconPrinter"
            :title="$t('No print layouts available')"
        />
        <BaseAlertComponent
            v-if="popupBlocked"
            type="error"
            message="The print window was blocked. Please allow pop-ups for this page."
            use-translation
            class="mt-4 !mb-0"
        />
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                @click="emit('close')"
            />
            <BaseUIButton
                label="Print"
                variant="primary"
                icon="IconPrinter"
                :disabled="!selectedLayout"
                @click="openPrintDialog"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from "vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import EmptyState from "@/Artwork/Feedback/EmptyState.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

defineOptions({name: 'PrintLayoutSelectorModal'})

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    printLayouts: {
        type: [Array, Object],
        default: () => [],
    },
})

const emit = defineEmits(['close']);

const activePrintLayouts = computed(() => Object.values(props.printLayouts ?? {}).filter(layout => layout?.is_active));
const selectedLayout = ref(activePrintLayouts.value[0] ?? null);
const popupBlocked = ref(false);

const openPrintDialog = () => {
    if (!selectedLayout.value) {
        return;
    }
    popupBlocked.value = false;
    const printWindow = window.open(route('project-print-layout.show', {
        project: props.project.id,
        projectPrintLayout: selectedLayout.value.id,
    }), '_blank', 'width=1000,height=1000');

    if (!printWindow) {
        popupBlocked.value = true;
        return;
    }

    // Warte darauf, dass das Fenster vollständig geladen ist
    printWindow.onload = () => {
        const checkReady = setInterval(() => {
            if (printWindow.document.readyState === 'complete') {
                clearInterval(checkReady);
                setTimeout(() => {
                    printWindow.print();
                }, 500); // Sicherheitsverzögerung für das Rendering
            }
        }, 100);
    };

    // Fenster nach dem Drucken oder Abbrechen schließen
    printWindow.onafterprint = () => {
        printWindow.close();
    };

    // Falls der Benutzer das Fenster vorher schließt
    printWindow.onbeforeunload = () => {
        printWindow.close();
    };
}
</script>
