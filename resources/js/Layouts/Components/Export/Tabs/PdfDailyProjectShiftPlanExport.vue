<template>
    <div class="mx-auto w-full max-w-4xl">
        <div class="flex flex-col space-y-6">
            <!-- What this export does -->
            <section>
                <h1 class="text-lg font-semibold text-zinc-900">
                    {{ $t('PDF_DAILY_PROJECT_SHIFT_PLAN_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ $t('Export the shift plan for this project as a PDF document.') }}
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
                            {{ $t('One day per page · Compact timeline layout') }}
                        </div>
                    </div>

                    <span class="shrink-0 inline-flex items-center rounded-full border border-zinc-200/70 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold text-zinc-700">
                        PDF
                    </span>
                </div>
            </section>

            <!-- Privacy mode -->
            <section class="rounded-2xl border border-zinc-200 bg-white px-4 py-4 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="grid size-10 place-items-center rounded-2xl bg-zinc-100 text-zinc-700">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V6l-8-3-8 3v6c0 6 8 10 8 10z" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-zinc-900">
                                    {{ $t('Privacy mode') }}
                                </div>
                                <div class="mt-0.5 text-xs leading-5 text-zinc-500">
                                    {{ $t('Hide personal data in the PDF (recommended for public sharing).') }}
                                </div>
                            </div>

                            <!-- Toggle -->
                            <button
                                type="button"
                                class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900/20"
                                :class="privacyMode ? 'bg-zinc-900' : 'bg-zinc-200'"
                                @click="privacyMode = !privacyMode"
                                :aria-pressed="privacyMode ? 'true' : 'false'"
                            >
                                <span class="sr-only">{{ $t('Toggle privacy mode') }}</span>
                                <span
                                    class="inline-block size-6 transform rounded-full bg-white shadow-sm transition"
                                    :class="privacyMode ? 'translate-x-5' : 'translate-x-1'"
                                />
                            </button>
                        </div>

                        <!-- Details -->
                        <div class="mt-3 rounded-xl border border-zinc-200/70 bg-zinc-50 px-3 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-zinc-600">
                                {{ $t('When enabled') }}
                            </div>

                            <ul class="mt-2 space-y-1.5 text-xs text-zinc-700">
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 size-1.5 rounded-full bg-zinc-400"></span>
                                    <span>{{ $t('Full names are replaced (e.g. “Max M.”).') }}</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 size-1.5 rounded-full bg-zinc-400"></span>
                                    <span>{{ $t('Contact details and internal notes are omitted (if present).') }}</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 size-1.5 rounded-full bg-zinc-400"></span>
                                    <span>{{ $t('Freelancers / providers are anonymized in the same way.') }}</span>
                                </li>
                            </ul>

                            <div class="mt-3 flex items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="privacyMode ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-200 text-zinc-700'"
                                >
                                    {{ privacyMode ? $t('ON') : $t('OFF') }}
                                </span>
                                <span class="text-[11px] text-zinc-500">
                                    {{ privacyMode ? $t('Safe to share externally.') : $t('Contains personal data.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Export -->
            <section class="flex items-center justify-end">
                <BaseUIButton
                    @click="handleExport"
                    :label="$t('Export PDF')"
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
const privacyMode = ref(false)
const loading = ref(false)

const handleExport = () => {
    const projectId = project.value?.id ?? null
    if (!projectId || loading.value) return

    loading.value = true
    try {
        const url = route("projects.exports.shift-plan", {
            project: projectId,
            privacyMode: privacyMode.value ? 1 : 0,
        })
        window.open(url, "_blank")
    } finally {
        // kurzer “Instant feedback” – falls der Tab sofort aufgeht
        window.setTimeout(() => (loading.value = false), 450)
    }
}
</script>
