<template>
    <div class="mx-auto w-full max-w-4xl">
        <div class="flex flex-col space-y-6">
            <!-- What this export does -->
            <section>
                <h1 class="text-lg font-semibold text-zinc-900">
                    {{ $t('EXCEL_SHIFT_PERSONNEL_PLAN_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ $t('Export the personnel plan for this project as an Excel file.') }}
                </p>
            </section>

            <!-- Project mini header -->
            <section class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="grid size-10 place-items-center rounded-2xl bg-zinc-900 text-white shadow-sm">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 7h10M7 12h10M7 17h6" />
                            <path d="M6 3h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-zinc-900">
                            {{ project?.name ?? $t('Project') }}
                        </div>
                        <div class="text-xs text-zinc-500">
                            {{ $t('One row per shift · Grouped by day') }}
                        </div>
                    </div>

                    <span class="shrink-0 inline-flex items-center rounded-full border border-zinc-200/70 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold text-zinc-700">
                        XLSX
                    </span>
                </div>
            </section>

            <!-- Number format choice -->
            <section class="rounded-2xl border border-zinc-200 bg-white px-4 py-4 shadow-sm">
                <div class="text-sm font-semibold text-zinc-900">
                    {{ $t('Number format') }}
                </div>
                <div class="mt-0.5 text-xs leading-5 text-zinc-500">
                    {{ $t('Choose how working hours are displayed in the Excel file.') }}
                </div>

                <div class="mt-3 space-y-2">
                    <button
                        type="button"
                        class="w-full rounded-xl border px-3 py-3 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900/20"
                        :class="numberFormat === 'readable'
                            ? 'border-zinc-900 bg-zinc-50'
                            : 'border-zinc-200/70 bg-white hover:bg-zinc-50'"
                        @click="numberFormat = 'readable'"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="grid size-4 shrink-0 place-items-center rounded-full border"
                                :class="numberFormat === 'readable' ? 'border-zinc-900' : 'border-zinc-300'"
                            >
                                <span v-if="numberFormat === 'readable'" class="size-2 rounded-full bg-zinc-900" />
                            </span>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-zinc-900">
                                    {{ $t('Readable format') }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $t('Working hours as text, e.g. “8 Std. 30 min”.') }}
                                </div>
                            </div>
                        </div>
                    </button>

                    <button
                        type="button"
                        class="w-full rounded-xl border px-3 py-3 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900/20"
                        :class="numberFormat === 'decimal'
                            ? 'border-zinc-900 bg-zinc-50'
                            : 'border-zinc-200/70 bg-white hover:bg-zinc-50'"
                        @click="numberFormat = 'decimal'"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="grid size-4 shrink-0 place-items-center rounded-full border"
                                :class="numberFormat === 'decimal' ? 'border-zinc-900' : 'border-zinc-300'"
                            >
                                <span v-if="numberFormat === 'decimal'" class="size-2 rounded-full bg-zinc-900" />
                            </span>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-zinc-900">
                                    {{ $t('Excel number format') }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $t('Working hours as decimal numbers (e.g. 8.5) incl. totals, ready for calculations.') }}
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
            </section>

            <!-- Export -->
            <section class="flex items-center justify-end">
                <BaseUIButton
                    @click="handleExport"
                    :label="$t('Export XLSX')"
                    icon="IconFileExport"
                    :disabled="!project?.id || loading"
                    is-add-button
                />
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from "vue"
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue"
import { useTranslation } from "@/Composeables/Translation.js"

const $t = useTranslation()
const emit = defineEmits(["close"])

const props = defineProps({
    configuration: { type: Object, required: false, default: () => ({}) },
})

const project = computed(() => props.configuration?.project ?? null)
const numberFormat = ref("readable")
const loading = ref(false)

const handleExport = () => {
    const projectId = project.value?.id ?? null
    if (!projectId || loading.value) return

    loading.value = true
    try {
        const url = route("projects.exports.shifts-personal-plan", {
            project: projectId,
            numberFormat: numberFormat.value,
        })
        window.open(url, "_blank")
    } finally {
        // kurzer “Instant feedback” – falls der Tab sofort aufgeht
        window.setTimeout(() => (loading.value = false), 450)
    }
}
</script>
