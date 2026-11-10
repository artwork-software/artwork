<template>
    <ArtworkBaseModal
        title="Export artist residencies"
        description="Export the artist residencies for this project."
        modal-size="sm:max-w-xl"
        @close="emits('close')"
    >
        <div class="space-y-6">
            <fieldset v-for="group in optionGroups" :key="group.name">
                <legend class="font-lexend text-sm font-medium text-text">{{ $t(group.legend) }}</legend>
                <p class="mt-0.5 text-xs text-text-muted">{{ $t(group.hint) }}</p>
                <div class="mt-3 grid gap-2" :class="group.options.length > 2 ? 'sm:grid-cols-3' : 'sm:grid-cols-2'">
                    <label
                        v-for="option in group.options"
                        :key="option.id"
                        :for="`artist-residency-export-${group.name}-${option.id}`"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border px-3.5 py-2.5 transition-colors duration-150 motion-reduce:transition-none has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-accent-600"
                        :class="group.model.value.id === option.id ? 'border-accent-600 bg-accent-50' : 'border-border-subtle bg-surface hover:bg-surface-hover'"
                    >
                        <input
                            :id="`artist-residency-export-${group.name}-${option.id}`"
                            v-model="group.model.value"
                            type="radio"
                            :name="`artist-residency-export-${group.name}`"
                            :value="option"
                            class="sr-only"
                        />
                        <span
                            class="box-border size-4 shrink-0 rounded-full border bg-surface"
                            :class="group.model.value.id === option.id ? 'border-[5px] border-accent-600' : 'border-border'"
                            aria-hidden="true"
                        ></span>
                        <span class="text-sm font-medium text-text">{{ $t(option.title) }}</span>
                    </label>
                </div>
            </fieldset>
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="emits('close')"
            />
            <BaseUIButton
                label="Export artist residencies"
                variant="primary"
                icon="IconFileExport"
                :processing="processing"
                @click="exportArtistResidencies"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

defineOptions({name: 'ExportArtistResidenciesModal'})

const props = defineProps({
    project: {
        type: Object,
        required: true
    }
})

const emits = defineEmits(['close'])

const languageMethods = [
    {id: 'de', title: 'German'},
    {id: 'en', title: 'English'},
]

const exportMethods = [
    {id: 'pdf', title: 'PDF-Export'},
    {id: 'excel', title: 'Excel-Export'},
    {id: 'per-diem-pdf', title: 'Per Diem Export'},
]

const selectedLanguage = ref(languageMethods[0])
const selectedExportMode = ref(exportMethods[0])
const processing = ref(false)

const optionGroups = [
    {
        name: 'type',
        legend: 'Export Type',
        hint: 'Select the export type for the artist residencies.',
        options: exportMethods,
        model: selectedExportMode,
    },
    {
        name: 'language',
        legend: 'Language',
        hint: 'Select the language for the export.',
        options: languageMethods,
        model: selectedLanguage,
    },
]

const pdfRoutes = {
    'pdf': 'artist-residencies.export-pdf',
    'per-diem-pdf': 'artist-residencies.export-per-diem-pdf',
}

const exportArtistResidencies = () => {
    const routeParameters = {
        project: props.project.id,
        language: selectedLanguage.value.id,
    }
    const pdfRoute = pdfRoutes[selectedExportMode.value.id]

    if (!pdfRoute) {
        window.open(route('artist-residencies.export-excel', routeParameters), '_blank', 'noopener');
        return
    }

    if (processing.value) {
        return
    }
    processing.value = true
    router.post(route(pdfRoute, routeParameters), {}, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>
