<template>
    <div class="mx-auto w-full max-w-4xl">
        <div class="flex flex-col space-y-6">
            <!-- Title + hint -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <BaseInput id="shiftPlanTitle" v-model="pdf.title" :label="$t('Heading')" />

                <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-4">
                    <div class="flex items-start gap-x-3">
                        <PropertyIcon name="IconExclamationCircle" class="size-5 min-h-5 min-w-5 text-blue-500"/>
                        <p class="text-sm text-blue-500">
                            {{ $t('The current shift plan view is exported: time period, active filters and project mode are taken over. Each calendar week is placed on its own page.') }}
                        </p>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500" v-if="config.startDate && config.endDate">
                        {{ $t('Period') }}: {{ formatDate(config.startDate) }} – {{ formatDate(config.endDate) }}
                    </p>
                </div>
            </section>

            <!-- Paper format + orientation + DPI -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Paper size -->
                    <div class="space-y-2">
                        <Listbox as="div" v-model="selectedPaperSize">
                            <ListboxLabel class="block text-sm font-medium text-zinc-700">
                                {{ $t('Paper size') }}
                            </ListboxLabel>
                            <div class="relative mt-1">
                                <ListboxButton
                                    class="relative w-full cursor-pointer rounded-xl border border-zinc-200 bg-white px-4 py-3 text-left text-sm hover:bg-zinc-50">
                                    <div class="block truncate">{{ selectedPaperSize.name }}</div>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <component :is="IconSelector" class="h-5 w-5 text-zinc-400" />
                                    </span>
                                </ListboxButton>
                                <transition
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute z-10 mt-2 max-h-60 w-full overflow-auto rounded-xl bg-white py-2 text-sm shadow-lg ring-1 ring-black/10 focus:outline-none">
                                        <ListboxOption
                                            v-for="paperSize in paperSizes"
                                            :key="paperSize.id"
                                            :value="paperSize"
                                            v-slot="{ active, selected }"
                                            as="template">
                                            <li :class="['relative cursor-pointer select-none py-2 pl-3 pr-9', active ? 'bg-zinc-900 text-white' : 'text-zinc-900']">
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                                    {{ paperSize.name }}
                                                </span>
                                                <span v-if="selected" :class="[active ? 'text-white' : 'text-zinc-900', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                    <component :is="IconCheck" class="h-5 w-5" />
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                    </div>

                    <!-- Orientation -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-zinc-700">
                            {{ $t('Paper orientation') }}
                        </label>
                        <fieldset>
                            <div class="flex gap-3">
                                <div v-for="paperOrientation in paperOrientations" :key="paperOrientation.id" class="relative flex-1">
                                    <input
                                        :id="`shiftplan-${paperOrientation.id}`"
                                        name="shiftplan-orientation"
                                        type="radio"
                                        :checked="paperOrientation.id === selectedPaperOrientation.id"
                                        class="peer absolute inset-0 h-0 w-0 opacity-0"
                                        @change="selectedPaperOrientation = paperOrientation"
                                    />
                                    <label
                                        :for="`shiftplan-${paperOrientation.id}`"
                                        class="block cursor-pointer rounded-xl border px-4 py-3 text-sm transition
                                        peer-checked:border-zinc-900 peer-checked:bg-zinc-900 peer-checked:text-white
                                        border-zinc-200 bg-white text-zinc-800 hover:bg-zinc-50 hover:text-primary">
                                        {{ paperOrientation.title }}
                                    </label>
                                </div>
                            </div>
                            <span class="mt-2 block text-xs text-zinc-500">
                                {{ $t('Landscape format is recommended so a whole calendar week fits on one page.') }}
                            </span>
                        </fieldset>
                    </div>

                    <!-- DPI -->
                    <div>
                        <BaseInput
                            id="shiftPlanDpi"
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
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { IconCheck, IconSelector } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()
const emits = defineEmits<{ (e: 'close'): void }>()
const props = defineProps<{ configuration?: Record<string, any> }>()

const config = props.configuration ?? {}

const paperSizes = [
    { id: 'a3', name: 'A3' },
    { id: 'a4', name: 'A4 (Standard)' },
    { id: 'a5', name: 'A5' },
]
const paperOrientations = [
    { id: 'landscape', title: $t('Landscape format') },
    { id: 'portrait', title: $t('Portrait format') },
]

const selectedPaperSize = ref(paperSizes[1])
const selectedPaperOrientation = ref(paperOrientations[0])

const pdf = useForm({
    title: config.projectName || $t('Shift plan'),
    start: config.startDate ?? null,
    end: config.endDate ?? null,
    projectId: config.projectId ?? null,
    isInProjectView: !!config.isInProjectView,
    isDailyView: !!config.isDailyView,
    highlightProjectId: config.highlightProjectId ?? null,
    paperSize: 'a4',
    paperOrientation: 'landscape',
    dpi: 96,
})

const formatDate = (value: string | null) => {
    if (!value) return '-'
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    return `${day}.${month}.${date.getFullYear()}`
}

const createPdf = () => {
    pdf.paperSize = selectedPaperSize.value.id
    pdf.paperOrientation = selectedPaperOrientation.value.id

    pdf.post(route('shift.plan.export.pdf'), { preserveScroll: true })
    emits('close')
}
</script>
