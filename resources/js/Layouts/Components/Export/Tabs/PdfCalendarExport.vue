<template>
    <div class="mx-auto w-full max-w-4xl"> <!-- zentrieren + begrenzen -->
        <div class="flex flex-col space-y-6"> <!-- mehr vertikaler Rhythmus -->

            <!-- Titel + Hinweis -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <BaseInput id="title" v-model="pdf.title" :label="$t('Heading')" />

                <div
                    v-if="showModalInformation"
                    class="rounded-xl border border-blue-200 bg-blue-50/70 p-4"
                >
                    <div class="flex items-start gap-x-3">
                        <PropertyIcon name="IconExclamationCircle" class="size-5 min-h-5 min-w-5 text-blue-500"/>
                        <p class="text-sm text-blue-500">
                            {{
                                $t(
                                    'If a project is specified, the project period is used for the export. If no project is specified, you can either specify the start and end date yourself or your current calendar start and end date will be used automatically.'
                                )
                            }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="mt-2 block w-fit text-xs text-red-500 underline"
                        @click="showModalInformation = false"
                    >
                        {{ $t('Close note') }}
                    </button>
                </div>
            </section>

            <!-- Projektwahl / Zeitraum -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4" :class="pdfSelectedProject ? 'pt-3' : 'pt-8'">
                    <ProjectSearch
                        v-if="!pdfSelectedProject"
                        @project-selected="addProjectToPdf"
                        no-project-groups
                        get-first-last-event
                    />
                    <div v-else class="flex flex-col gap-2">
                        <h3 class="text-base font-semibold text-zinc-900">
                            {{ pdfSelectedProject.name }}
                        </h3>
                        <p
                            class="text-sm text-zinc-700"
                            v-if="pdfSelectedProject?.first_event && pdfSelectedProject?.last_event"
                        >
                            {{ $t('Project period') }}:
                            <span class="text-blue-700" v-if="pdfSelectedProject.first_event.start_time">
                {{ pdfSelectedProject.first_event.start_time }}
              </span>
                            –
                            <span class="text-blue-700" v-if="pdfSelectedProject.last_event.end_time">
                {{ pdfSelectedProject.last_event.end_time }}
              </span>
                        </p>
                        <button
                            type="button"
                            class="w-fit text-left text-xs text-blue-700 underline"
                            @click="pdfSelectedProject = null"
                        >
                            {{ $t('Cancel project selection') }}
                        </button>
                    </div>

                    <div class="mt-4">
                        <LastedProjects :limit="10" @select="addProjectToPdf" />
                    </div>
                </div>

                <!-- Zeitraum nur wenn kein Projekt -->
                <div v-if="!pdfSelectedProject" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput type="date" v-model="pdf.start" :label="$t('Start date')" id="start" />
                    <BaseInput type="date" v-model="pdf.end" :label="$t('End date')" id="end" />
                </div>
            </section>

            <!-- Papierformat + Orientierung + DPI -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Papierformat -->
                    <div class="space-y-2">
                        <Listbox as="div" v-model="selectedPaperSize">
                            <ListboxLabel class="block text-sm font-medium text-zinc-700">
                                {{ $t('Paper size') }}
                            </ListboxLabel>
                            <div class="relative mt-1">
                                <ListboxButton
                                    class="relative w-full cursor-pointer rounded-xl border border-zinc-200 bg-white px-4 py-3 text-left text-sm hover:bg-zinc-50"
                                >
                                    <div class="block truncate">{{ selectedPaperSize.name }}</div>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg class="h-5 w-5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                    </svg>
                  </span>
                                </ListboxButton>
                                <transition
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <ListboxOptions
                                        class="absolute z-10 mt-2 max-h-60 w-full overflow-auto rounded-xl bg-white py-2 text-sm shadow-lg ring-1 ring-black/10 focus:outline-none"
                                    >
                                        <ListboxOption
                                            v-for="paperSize in paperSizes"
                                            :key="paperSize.id"
                                            :value="paperSize"
                                            v-slot="{ active, selected }"
                                            as="template"
                                        >
                                            <li
                                                :class="[
                          'relative cursor-pointer select-none py-2 pl-3 pr-9',
                          active ? 'bg-zinc-900 text-white' : 'text-zinc-900'
                        ]"
                                            >
                        <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                          {{ paperSize.name }}
                        </span>
                                                <span
                                                    v-if="selected"
                                                    :class="[
                            active ? 'text-white' : 'text-zinc-900',
                            'absolute inset-y-0 right-0 flex items-center pr-4'
                          ]"
                                                >
                          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M4.5 12.75l6 6 9-13.5" />
                          </svg>
                        </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                    </div>

                    <!-- Orientierung -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-zinc-700">
                            {{ $t('Paper orientation') }}
                        </label>
                        <fieldset>
                            <legend class="sr-only">Paper orientation</legend>
                            <div class="flex gap-3">
                                <div
                                    v-for="paperOrientation in paperOrientations"
                                    :key="paperOrientation.id"
                                    class="relative flex-1"
                                >
                                    <input
                                        :id="paperOrientation.id"
                                        name="notification-method"
                                        type="radio"
                                        :checked="paperOrientation.id === checkedOrientation"
                                        class="peer absolute inset-0 h-0 w-0 opacity-0"
                                        :disabled="orientationDisabled"
                                        @change="changePaperOrientation(paperOrientation)"
                                    />
                                    <label
                                        :for="paperOrientation.id"
                                        class="block cursor-pointer rounded-xl border px-4 py-3 text-sm transition
                           peer-checked:border-zinc-900 peer-checked:bg-zinc-900 peer-checked:text-white
                           border-zinc-200 bg-white text-zinc-800 hover:bg-zinc-50"
                                        :class="orientationDisabled ? 'opacity-60 cursor-not-allowed' : ''"
                                    >
                                        {{ paperOrientation.title }}
                                    </label>
                                </div>
                            </div>
                            <span class="mt-2 block text-xs text-red-500" v-if="orientationDisabled">
                {{ $t('The A6 format is only possible in landscape format.') }}
              </span>
                        </fieldset>
                    </div>

                    <!-- DPI -->
                    <div class="">
                        <BaseInput
                            id="dpi"
                            v-model="pdf.dpi"
                            :label="$t('Resolution (DPI) (Standard: 72) (Maximum: 300)')"
                        />
                    </div>
                </div>
            </section>

            <!-- Export -->
            <section class="flex items-center justify-end">
                <BaseUIButton
                    @click="createPdf()"
                    :label="$t('Export PDF')"
                    icon="IconFileExport"
                    is-add-button
                />
            </section>

        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import NumberInputComponent from '@/Components/Inputs/NumberInputComponent.vue'
import ProjectSearch from '@/Components/SearchBars/ProjectSearch.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import LastedProjects from '@/Artwork/LastedProjects.vue'
import { useTranslation } from '@/Composeables/Translation.js'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const $t = useTranslation()
const emits = defineEmits<{ (e: 'closed', value: boolean): void }>()
const props = defineProps<{ pdfTitle?: string; project?: any }>()

const showModalInformation = ref(true)

const paperSizes = [
    { id: 'a4', name: 'A4 (Standard)' },
    { id: 'a3', name: 'A3' },
    { id: 'a5', name: 'A5' },
    { id: 'a6', name: 'A6' }
]
const paperOrientations = [
    { id: 'portrait', title: $t('Portrait format') },
    { id: 'landscape', title: $t('Landscape format') }
]

const pdf = useForm({
    title: props.project ? props.project.name : $t('Room assignment'),
    start: null as string | null,
    end: null as string | null,
    paperSize: null as string | null,
    paperOrientation: null as string | null,
    project: null as number | null,
    dpi: 72
})

const pdfSelectedProject = ref<any | null>(null)
const selectedPaperSize = ref<{ id: string; name: string }>({ id: 'a4', name: 'A4 (Standard)' })
const selectedPaperOrientation = ref<{ id: 'portrait' | 'landscape'; title: string }>({
    id: 'portrait',
    title: $t('Portrait format')
})
const checkedOrientation = ref<'portrait' | 'landscape'>('portrait')
const orientationDisabled = ref(false)

const closeModal = (bool: boolean) => emits('closed', bool)

const createPdf = () => {
    pdf.paperSize = selectedPaperSize.value.id
    pdf.paperOrientation = selectedPaperOrientation.value.id

    if (props.project) {
        pdf.project = props.project.id
    }
    if (pdfSelectedProject.value) {
        pdf.project = pdfSelectedProject.value.id
    }

    pdf.post(route('calendar.export.pdf'), { preserveScroll: true })
    closeModal(true)
}

const changePaperOrientation = (orientation: { id: 'portrait' | 'landscape'; title: string }) => {
    selectedPaperOrientation.value = orientation
    checkedOrientation.value = orientation.id
}

const addProjectToPdf = (project: any) => {
    pdfSelectedProject.value = project
}

// A6 erzwingt Landscape (unveränderte Logik)
watch(
    () => selectedPaperSize.value,
    () => {
        if (selectedPaperSize.value.id === 'a6') {
            checkedOrientation.value = 'landscape'
            orientationDisabled.value = true
            changePaperOrientation({ id: 'landscape', title: $t('Landscape format') })
        } else {
            checkedOrientation.value = 'portrait'
            orientationDisabled.value = false
            changePaperOrientation({ id: 'portrait', title: $t('Portrait format') })
        }
    },
    { immediate: true }
)
</script>
