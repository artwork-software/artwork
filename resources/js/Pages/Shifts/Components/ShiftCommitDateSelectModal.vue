<template>
    <ArtworkBaseModal
        :title="isShiftCommitWorkflowEnabled ? 'Submit duty roster for approval' : 'Commit duty roster'"
        :description="isShiftCommitWorkflowEnabled
            ? 'Select the calendar week and the crafts. One approval request per craft is sent to the approvers.'
            : 'Select the calendar week and the crafts. All shifts of these crafts in the week are committed and appear in the personal rosters.'"
        @close="$emit('close')"
    >
        <!-- Neutrale Hinweisbox: aktiver Freigabe-Workflow + Genehmiger*innen (shared Prop shiftCommitApprovers) -->
        <div class="mb-4" v-if="isShiftCommitWorkflowEnabled">
            <div class="rounded-xl border border-accent-100 bg-accent-50/50 px-4 py-3 flex gap-3 items-start">
                <PropertyIcon name="IconInfoCircle" class="h-5 w-5 text-accent-600 shrink-0 mt-0.5" :stroke-width="1.5" />
                <div class="min-w-0 text-xs text-text-muted leading-relaxed space-y-1">
                    <p>{{ $t('The approval workflow is active: the duty roster is not committed directly but submitted to the approvers. Once released, the shifts are committed automatically.') }}</p>
                    <p v-if="approverNames.length">
                        <span class="font-medium text-text">{{ $t('Approvers') }}:</span>
                        {{ approverNames.join(', ') }}
                    </p>
                    <p v-else>
                        {{ $t('No approvers are set up yet. Ask an administrator to add approvers under Shift settings → Approval workflow.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Eingaben -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <BaseInput
                v-model="newShiftCommitForm.week_number"
                type="number"
                min="1"
                max="53"
                label="Calendar Week"
                id="commit_week"
                :error="serverErrors.week_number"
            />

            <BaseInput
                v-model="newShiftCommitForm.year"
                :min="currentYear"
                type="number"
                label="Year"
                id="commit_year"
                :error="serverErrors.year"
            />

            <div class="col-span-full">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-text font-lexend">
                        {{ $t('Craft') }}
                        <span v-if="selectedCrafts.length > 0" class="ml-1 text-xs font-normal text-text-subtle">
                            ({{ selectedCrafts.length }}/{{ crafts.length }})
                        </span>
                    </span>
                    <button
                        v-if="crafts.length > 0"
                        type="button"
                        class="text-xs font-lexend text-accent-600 hover:text-accent-600 transition-colors"
                        @click="toggleAllCrafts"
                    >
                        {{ allCraftsSelected ? $t('Deselect all crafts') : $t('Select all crafts') }}
                    </button>
                </div>

                <!-- Alle Gewerke direkt sichtbar als Toggle-Chips -->
                <div class="mt-2 flex flex-wrap gap-2 max-h-40 overflow-y-auto pr-1">
                    <button
                        v-for="craft in crafts"
                        :key="craft.id"
                        type="button"
                        :aria-pressed="isCraftSelected(craft)"
                        class="group inline-flex items-center gap-1.5 shrink-0 px-3 py-1.5 rounded-full border text-xs font-lexend transition-colors"
                        :class="isCraftSelected(craft) ? 'bg-accent-50 border-accent-200 text-accent-700'
                            : 'bg-white border-border-subtle text-text-muted hover:bg-surface-sunken hover:border-border'"
                        @click="toggleCraft(craft)"
                    >
                        <span
                            v-if="craft.color"
                            class="inline-block size-2.5 rounded-full shrink-0"
                            :style="{ backgroundColor: craft.color }"
                        ></span>
                        <span class="truncate max-w-40">{{ craft.name }}</span>
                        <svg
                            v-if="isCraftSelected(craft)"
                            class="size-3.5 shrink-0 text-accent-600"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                            stroke-linecap="round" stroke-linejoin="round"
                        >
                            <path d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>

                <!-- Nur planbare Gewerke werden angeboten; hat die Person keine, gibt es nichts festzuschreiben -->
                <p v-if="crafts.length === 0" class="mt-2 text-xs text-text-subtle font-lexend">
                    {{ $t('No crafts available for you to commit.') }}
                </p>

                <p v-if="craftError" class="mt-1 text-xs text-danger font-lexend">
                    {{ $t('Please select at least one craft.') }}
                </p>
                <!-- Serverfehler zu den Gewerken (z. B. fremdes Gewerk → 422 aus CommitShiftsRequest) -->
                <p v-else-if="serverErrors.crafts" class="mt-1 text-xs text-danger font-lexend">
                    {{ serverErrors.crafts }}
                </p>
                <p v-else-if="isShiftCommitWorkflowEnabled" class="mt-1 text-xs text-text-subtle font-lexend">
                    {{ $t('One separate request per selected craft will be created.') }}
                </p>
            </div>
        </div>

        <!-- Date Range Anzeige -->
        <div class="mt-5">
            <!-- Fester Container, damit nichts springt -->
            <div
                class="rounded-2xl border border-border-subtle bg-surface-sunken/80 px-4 py-3 shadow-sm flex gap-3 items-start min-h-[3.25rem]"
            >
                <!-- kleines Icon / Badge -->
                <div class="mt-0.5 h-7 w-7 rounded-full bg-surface-sunken flex items-center justify-center text-[10px] font-semibold text-text-subtle">
                    KW
                </div>

                <div class="flex-1 flex flex-col justify-center">
                    <!-- Loading State mit Animation -->
                    <transition name="fade" mode="out-in">
                        <div
                            v-if="isLoadingDateRange"
                            key="loading"
                            class="flex flex-col gap-1"
                        >
                            <p class="text-xs text-text-subtle font-lexend">
                                {{ $t('Loading date range for the selected calendar week...') }}
                            </p>

                            <!-- animierte Punkte -->
                            <div class="flex items-center gap-1 mt-1">
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-border-strong animate-bounce"></span>
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-border-strong animate-bounce [animation-delay:0.12s]"></span>
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-border-strong animate-bounce [animation-delay:0.24s]"></span>
                            </div>
                        </div>

                        <!-- Erfolgreicher DateRange -->
                        <div
                            v-else-if="dateRange.start_date && dateRange.end_date && !dateRangeError"
                            key="range"
                            class="flex flex-col gap-1"
                        >
                            <p class="text-xs text-text-subtle font-lexend">
                                {{ $t('This calendar week covers the following period:') }}
                            </p>
                            <p class="text-sm font-medium text-text mt-0.5">
                                {{ dateRange.start_date }} – {{ dateRange.end_date }}
                            </p>
                        </div>

                        <!-- Fehlerzustand -->
                        <div
                            v-else-if="dateRangeError"
                            key="error"
                            class="flex flex-col gap-1"
                        >
                            <p class="text-xs text-danger font-lexend">
                                {{ $t(dateRangeError) }}
                            </p>
                        </div>

                        <!-- Fallback / initialer Zustand -->
                        <div
                            v-else
                            key="empty"
                            class="flex flex-col gap-1"
                        >
                            <p class="text-xs text-text-subtle font-lexend">
                                {{ $t('Select a calendar week and year to see the corresponding date range.') }}
                            </p>
                        </div>
                    </transition>
                </div>
            </div>
        </div>


        <!-- Sonstige Serverfehler (nicht einem Feld zugeordnet) — vorher landeten 422er nur in der Konsole -->
        <div
            v-if="serverErrors.general"
            class="mt-4 rounded-xl border border-danger-border bg-danger-surface px-4 py-3 text-xs text-danger font-lexend"
        >
            {{ serverErrors.general }}
        </div>

        <!-- Aktionen -->
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <BaseUIButton
                is-cancel-button
                :disabled="newShiftCommitForm.processing"
                @click="$emit('close')"
                :label="$t('Cancel')"
            />

            <div class="flex flex-col xs:flex-row gap-2">
                <BaseUIButton
                    v-if="isShiftCommitWorkflowEnabled"
                    :label="$t('Submit for approval')"
                    is-add-button
                    :processing="newShiftCommitForm.processing"
                    @click="submit"
                />
                <BaseUIButton
                    v-else
                    :label="$t('Commit duty roster')"
                    is-add-button
                    :processing="newShiftCommitForm.processing"
                    @click="submitWithoutWorkflow"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import {useForm, usePage} from '@inertiajs/vue3'
import axios from 'axios'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

const emit = defineEmits(['close'])

const props = defineProps({
    dateArray: Array,
    crafts: Array,
    // Gewerke, die die Person festschreiben darf (CraftScopeService); null = keine Einschränkung (Admin)
    plannableCraftIds: {
        type: Array,
        default: null,
    },
});

/**
 * Hilfsfunktion für ISO-Kalenderwoche
 */
const getIsoWeek = (date) => {
    const tmp = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))
    const dayNum = tmp.getUTCDay() || 7
    tmp.setUTCDate(tmp.getUTCDate() + 4 - dayNum)
    const yearStart = new Date(Date.UTC(tmp.getUTCFullYear(), 0, 1))
    const weekNo = Math.ceil(((tmp - yearStart) / 86400000 + 1) / 7)
    return weekNo
}

const today = new Date()
const currentYear = today.getFullYear()
const currentWeek = getIsoWeek(today)

const newShiftCommitForm = useForm({
    week_number: currentWeek,
    year: currentYear,
    craft_ids: [],
})

const dateRange = ref({
    start_date: null,
    end_date: null,
})
const isLoadingDateRange = ref(false)
const dateRangeError = ref(null)
const isShiftCommitWorkflowEnabled = ref(usePage().props.shiftCommitWorkflow)
// Genehmiger*innen-Namen aus HandleInertiaRequests (nur bei aktivem Workflow gefüllt)
const approverNames = computed(() =>
    (usePage().props.shiftCommitApprovers ?? [])
        .map((approver) => approver?.name)
        .filter((name) => typeof name === 'string' && name.trim() !== '')
)
// Nur planbare Gewerke anbieten — „alle auswählen" mit fremden Gewerken liefe sonst in den 422
// aus CommitShiftsRequest (Nicht-Admins dürfen nur Gewerke festschreiben, die sie planen dürfen).
const crafts = computed(() => {
    const all = props.crafts || []
    if (!Array.isArray(props.plannableCraftIds)) {
        return all
    }
    const allowed = new Set(props.plannableCraftIds.map((id) => Number(id)))
    return all.filter((craft) => allowed.has(Number(craft.id)))
})
const selectedCrafts = ref([])
const craftError = ref(false)

// Serverseitige Validierungsfehler (422) sichtbar machen: Feldfehler an KW/Jahr, Gewerksfehler
// unter der Gewerksliste, alles andere als allgemeiner Block.
const firstMessage = (value) => (Array.isArray(value) ? value[0] : value) || null
const serverErrors = computed(() => {
    const errors = newShiftCommitForm.errors || {}
    const craftKeys = Object.keys(errors).filter((key) => key === 'craft_ids' || key === 'craft_id' || key.startsWith('craft_ids.'))
    const knownKeys = new Set(['week_number', 'year', ...craftKeys])
    const generalKey = Object.keys(errors).find((key) => !knownKeys.has(key))

    return {
        week_number: firstMessage(errors.week_number) || '',
        year: firstMessage(errors.year) || '',
        crafts: craftKeys.length ? firstMessage(errors[craftKeys[0]]) : null,
        general: generalKey ? firstMessage(errors[generalKey]) : null,
    }
})

const allCraftsSelected = computed(
    () => crafts.value.length > 0 && selectedCrafts.value.length === crafts.value.length
)

const isCraftSelected = (craft) => selectedCrafts.value.some((c) => c.id === craft.id)

const toggleCraft = (craft) => {
    selectedCrafts.value = isCraftSelected(craft)
        ? selectedCrafts.value.filter((c) => c.id !== craft.id)
        : [...selectedCrafts.value, craft]
}

// „Alle auswählen" nimmt nur die planbaren Gewerke (crafts ist bereits gefiltert)
const toggleAllCrafts = () => {
    selectedCrafts.value = allCraftsSelected.value ? [] : [...crafts.value]
}

// Sobald eine Auswahl getroffen wurde, Fehlerhinweise zurücksetzen
watch(selectedCrafts, (value) => {
    if (value.length > 0) {
        craftError.value = false
    }
    newShiftCommitForm.clearErrors()
})

const getDateRangeByCalendarWeekAndYear = async (week, year) => {
    if (!week || !year) return

    isLoadingDateRange.value = true
    dateRangeError.value = null

    try {
        const response = await axios.get(route('api.helper.calendar-week'), {
            params: { week_number: week, year },
        })

        dateRange.value = response.data || { start_date: null, end_date: null }
    } catch (error) {
        console.error(error)
        dateRange.value = { start_date: null, end_date: null }
        dateRangeError.value =
            'Unable to load date range for the selected calendar week. Please try again.'
    } finally {
        isLoadingDateRange.value = false
    }
}

// Watch auf KW & Jahr – triggert API Call
watch(
    [() => newShiftCommitForm.week_number, () => newShiftCommitForm.year],
    ([newWeek, newYear]) => {
        newShiftCommitForm.clearErrors('week_number', 'year')
        if (newWeek && newYear) {
            getDateRangeByCalendarWeekAndYear(newWeek, newYear)
        }
    },
    { immediate: true } // direkt beim Öffnen mit aktueller KW laden
)

// optional: beim Mount einmal initial laden (falls sich das Formular später noch ändert)
onMounted(() => {
    getDateRangeByCalendarWeekAndYear(newShiftCommitForm.week_number, newShiftCommitForm.year)
})

const submitToRoute = (routeName) => {
    if (newShiftCommitForm.processing) {
        return
    }

    if (selectedCrafts.value.length === 0) {
        craftError.value = true
        return
    }

    newShiftCommitForm.craft_ids = selectedCrafts.value.map((craft) => craft.id)

    // Erfolgsmeldung kommt als Flash über den globalen Toast im AppLayout
    // (commit-shift-workflow-request.store liefert sie; shifts.commit derzeit ohne Text).
    newShiftCommitForm.post(route(routeName), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close')
        },
        // Fehler werden über newShiftCommitForm.errors im Modal angezeigt (serverErrors)
        onError: () => {},
    })
}

const submit = () => submitToRoute('commit-shift-workflow-request.store')

const submitWithoutWorkflow = () => submitToRoute('shifts.commit')
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease-out;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
