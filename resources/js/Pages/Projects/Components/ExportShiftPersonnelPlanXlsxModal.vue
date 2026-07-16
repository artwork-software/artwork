<template>
    <ArtworkBaseModal
        :title="$t('Export Shift personnel plan as xlsx')"
        :description="$t('Export the personnel plan for this project as an Excel file.')"
        @close="emit('close')"
    >
        <div class="space-y-5">
            <!-- Project mini header -->
            <div class="rounded-2xl border border-zinc-200/70 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="grid size-10 place-items-center rounded-2xl bg-zinc-900 text-white shadow-sm">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 7h10M7 12h10M7 17h6" />
                            <path d="M6 3h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-zinc-900">
                            {{ props.project?.name ?? $t('Project') }}
                        </div>
                        <div class="text-xs text-zinc-500">
                            {{ $t('One row per shift · Grouped by day') }}
                        </div>
                    </div>

                    <span class="shrink-0 inline-flex items-center rounded-full border border-zinc-200/70 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold text-zinc-700">
                        XLSX
                    </span>
                </div>
            </div>

            <!-- Number format choice -->
            <div class="rounded-2xl border border-zinc-200/70 bg-white px-4 py-4 shadow-sm">
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
            </div>

            <!-- Actions -->
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                <button
                    type="button"
                    class="h-11 w-full sm:w-auto rounded-2xl border border-zinc-200/70 bg-white px-4 text-sm font-semibold text-zinc-800 shadow-sm transition hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900/20"
                    @click="emit('close')"
                >
                    {{ $t('Cancel') }}
                </button>

                <button
                    type="button"
                    class="h-11 w-full sm:w-auto rounded-2xl bg-zinc-900 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900/20 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!props.project?.id || loading"
                    @click="handleExport"
                >
                    <span v-if="!loading" class="inline-flex items-center gap-2">
                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3v10" />
                            <path d="M8 9l4 4 4-4" />
                            <path d="M4 17v3h16v-3" />
                        </svg>
                        {{ $t('Export XLSX') }}
                    </span>

                    <span v-else class="inline-flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                            <path class="opacity-75" fill="currentColor" d="M12 2a10 10 0 0 1 10 10h-2A8 8 0 0 0 12 4V2z" />
                        </svg>
                        {{ $t('Preparing…') }}
                    </span>
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue"
import { ref } from "vue"

const emit = defineEmits(["close"])

const props = defineProps({
    project: { type: Object, required: false, default: null },
})

const numberFormat = ref("readable")
const loading = ref(false)

const openExportRouteInNewTab = () => {
    const projectId = props.project?.id ?? null
    if (!projectId) return

    const url = route("projects.exports.shifts-personal-plan", {
        project: projectId,
        numberFormat: numberFormat.value,
    })
    window.open(url, "_blank")
}

const handleExport = async () => {
    if (!props.project?.id || loading.value) return
    loading.value = true
    try {
        openExportRouteInNewTab()
    } finally {
        // kurzer “Instant feedback” – falls der Tab sofort aufgeht
        window.setTimeout(() => (loading.value = false), 450)
    }
}
</script>

<style scoped>
/* bewusst leer: Tailwind regelt */
</style>
