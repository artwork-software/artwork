<template>
    <ArtworkBaseModal
        :title="$t('Find replacement for {name}', { name: declinedName })"
        :description="headerDescription"
        modal-size="sm:max-w-3xl"
        @close="$emit('close')"
    >
        <div class="space-y-4">
            <!-- Absage-Kommentar der Person -->
            <div
                v-if="slot?.confirmation_comment"
                class="flex items-start gap-2 rounded-lg border border-danger-border bg-danger-surface px-3 py-2 text-xs text-danger"
            >
                <PropertyIcon name="IconMessageCircleX" class="h-4 w-4 shrink-0 mt-px" stroke-width="1.5" />
                <span>
                    <span class="font-semibold">{{ $t('Reason for declining') }}<span v-if="slot?.confirmation_at"> ({{ formatDate(slot.confirmation_at) }})</span>:</span>
                    „{{ slot.confirmation_comment }}“
                </span>
            </div>

            <BaseInput
                id="shift-replacement-search"
                v-model="search"
                type="text"
                :label="$t('Search person')"
                :show-label="false"
                :placeholder="$t('Search person')"
                is-small
            />

            <div v-if="loading" class="py-8 text-center text-sm text-text-subtle">
                {{ $t('Loading candidates …') }}
            </div>

            <div v-else-if="loadError" class="rounded-lg border border-danger-border bg-danger-surface px-3 py-2 text-xs text-danger">
                {{ loadError }}
            </div>

            <div
                v-else-if="candidates.length === 0"
                class="rounded-lg border border-dashed border-border px-4 py-6 text-center text-sm text-text-subtle"
            >
                {{ $t('No suitable person is free – you can still assign someone via drag & drop.') }}
            </div>

            <div v-else-if="filteredCandidates.length === 0" class="py-6 text-center text-sm text-text-subtle">
                {{ $t('No person matches your search.') }}
            </div>

            <ul v-else class="divide-y divide-border-subtle max-h-[50vh] overflow-y-auto -mx-1 px-1">
                <li
                    v-for="candidate in filteredCandidates"
                    :key="candidateKey(candidate)"
                    class="py-2"
                >
                    <div class="flex items-center gap-3">
                        <img
                            :src="candidate.profile_photo_url"
                            alt=""
                            class="h-9 w-9 shrink-0 rounded-full object-cover"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="truncate text-sm font-semibold text-text">{{ candidate.full_name }}</span>
                                <span
                                    v-if="candidate.type === 'freelancer'"
                                    class="shrink-0 rounded-full bg-surface-sunken px-1.5 py-px text-[10px] text-text-subtle"
                                >
                                    {{ $t('Freelancer') }}
                                </span>
                                <span
                                    v-if="candidate.balance_label"
                                    class="shrink-0 rounded-full bg-surface-inverse px-1.5 py-px text-[10px] font-semibold tabular-nums"
                                    :class="balanceClass(candidate)"
                                    v-tooltip.bottom="{ value: balanceTooltip, appendTo: 'body', class: 'aw-tooltip', position: 'bottom', useTranslation: false }"
                                >
                                    {{ candidate.balance_label }}
                                </span>
                            </div>
                            <div class="text-xs text-text-subtle truncate">
                                {{ candidate.qualification_name || slot?.qualification_name || '' }}
                                <span v-if="candidate.craft_abbreviation"> · {{ candidate.craft_abbreviation }}</span>
                            </div>
                            <ul v-if="candidate.hints?.length" class="mt-0.5 space-y-px">
                                <li
                                    v-for="(hint, index) in candidate.hints"
                                    :key="index"
                                    class="flex items-center gap-1 text-[11px] text-warning"
                                >
                                    <PropertyIcon name="IconAlertTriangle" class="h-3 w-3 shrink-0" stroke-width="1.5" />
                                    <span>{{ hint }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="shrink-0">
                            <BaseUIButton
                                v-if="!isPending(candidate)"
                                type="button"
                                size="sm"
                                variant="primary"
                                :disabled="submitting"
                                :label="$t('Assign')"
                                @click="pendingKey = candidateKey(candidate)"
                            />
                        </div>
                    </div>

                    <!-- Inline-Bestätigung: „X ersetzt Y" -->
                    <div
                        v-if="isPending(candidate)"
                        class="mt-2 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border bg-surface-sunken px-3 py-2"
                    >
                        <span class="text-xs text-text">
                            {{ $t('{replacement} replaces {declined}', { replacement: candidate.full_name, declined: declinedName }) }}
                            <span v-if="isCommitted" class="block text-[11px] text-text-subtle">
                                {{ $t('Both persons will be notified.') }}
                            </span>
                        </span>
                        <div class="flex items-center gap-2">
                            <BaseUIButton
                                type="button"
                                size="sm"
                                is-cancel-button
                                :disabled="submitting"
                                :label="$t('Cancel')"
                                @click="pendingKey = null"
                            />
                            <BaseUIButton
                                type="button"
                                size="sm"
                                variant="primary"
                                :processing="submitting"
                                :disabled="submitting"
                                :label="$t('Confirm')"
                                @click="confirmReplacement(candidate)"
                            />
                        </div>
                    </div>
                </li>
            </ul>

            <div v-if="submitError" class="rounded-lg border border-danger-border bg-danger-surface px-3 py-2 text-xs text-danger">
                {{ submitError }}
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import dayjs from 'dayjs'
import { useI18n } from 'vue-i18n'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

/**
 * „Ersatz suchen" nach Absage: Kandidat*innen des Gewerks mit der Funktion des
 * abgesagten Platzes (frei am Tag, ohne Überschneidung), Zuweisen tauscht die
 * Zuweisung serverseitig in einer Transaktion (shift.replace-worker).
 */
const props = defineProps({
    // Schicht-Payload des Plans (ShiftDTO) oder Tagesansicht
    shift: { type: Object, required: true },
    // Abgesagte Person aus shift.workers (mit pivot.id, pivot.confirmation_*)
    worker: { type: Object, required: true },
    // Optionaler Lookup für den Funktionsnamen (Fallback, wenn der Server keinen liefert)
    shiftQualifications: { type: [Array, Object], default: () => [] },
})

const emit = defineEmits(['close', 'replaced'])

const { t } = useI18n()

const loading = ref(true)
const loadError = ref(null)
const submitting = ref(false)
const submitError = ref(null)
const search = ref('')
const slot = ref(null)
const candidates = ref([])
const pendingKey = ref(null)

const declinedName = computed(() =>
    props.worker?.name
    || props.worker?.full_name
    || [props.worker?.first_name, props.worker?.last_name].filter(Boolean).join(' ')
    || slot.value?.declined_name
    || ''
)

const isCommitted = computed(() => !!(props.shift?.isCommitted ?? props.shift?.is_committed))

const qualificationNameFallback = computed(() => {
    const list = Array.isArray(props.shiftQualifications)
        ? props.shiftQualifications
        : Object.values(props.shiftQualifications || {})
    const id = props.worker?.pivot?.shift_qualification_id
    return list.find((q) => Number(q?.id) === Number(id))?.name ?? ''
})

const headerDescription = computed(() => {
    const parts = []
    const date = slot.value?.date
        ?? (props.shift?.startDate ? dayjs(props.shift.startDate).format('DD.MM.YYYY') : null)
        ?? (props.shift?.start_date ? dayjs(props.shift.start_date).format('DD.MM.YYYY') : null)
    if (date) parts.push(date)
    const start = slot.value?.start ?? normalizeTime(props.worker?.pivot?.start_time ?? props.shift?.start)
    const end = slot.value?.end ?? normalizeTime(props.worker?.pivot?.end_time ?? props.shift?.end)
    if (start && end) parts.push(`${start} – ${end}`)
    const craft = slot.value?.craft_name ?? props.shift?.craft?.name ?? props.shift?.craft?.abbreviation
    if (craft) parts.push(craft)
    const qualification = slot.value?.qualification_name ?? qualificationNameFallback.value
    if (qualification) parts.push(qualification)
    return parts.join(' · ')
})

const filteredCandidates = computed(() => {
    const term = search.value.trim().toLowerCase()
    if (!term) return candidates.value
    return candidates.value.filter((c) => (c.full_name || '').toLowerCase().includes(term))
})

const balanceTooltip = computed(() => {
    const yesterday = dayjs().subtract(1, 'day').format('DD.MM.YYYY')
    return t('As of: nightly booking up to {0}', [yesterday])
})

function candidateKey(candidate) {
    return `${candidate.type}:${candidate.id}`
}

function isPending(candidate) {
    return pendingKey.value === candidateKey(candidate)
}

// Gleiche Farblogik wie useWorkTimeBalanceBadge (Plus grün, Minus rot, Null neutral)
function balanceClass(candidate) {
    const minutes = candidate.balance_minutes
    if (typeof minutes !== 'number') return 'text-white'
    if (minutes > 0) return 'text-success'
    if (minutes < 0) return 'text-danger'
    return 'text-white'
}

function normalizeTime(value) {
    if (!value) return null
    return String(value).slice(0, 5)
}

function formatDate(value) {
    return value ? dayjs(value).format('DD.MM.YYYY') : ''
}

async function loadCandidates() {
    loading.value = true
    loadError.value = null
    try {
        const { data } = await axios.get(
            route('shift.replacement-candidates', { shift: props.shift.id }),
            { params: { shift_worker_id: props.worker.pivot.id } }
        )
        slot.value = data.slot ?? null
        candidates.value = Array.isArray(data.candidates) ? data.candidates : []
    } catch (error) {
        loadError.value = error?.response?.data?.message || t('Loading failed')
    } finally {
        loading.value = false
    }
}

async function confirmReplacement(candidate) {
    if (submitting.value) return
    submitting.value = true
    submitError.value = null
    try {
        const { data } = await axios.post(
            route('shift.replace-worker', { shift: props.shift.id }),
            {
                shift_worker_id: props.worker.pivot.id,
                replacement_type: candidate.type,
                replacement_id: candidate.id,
                shift_qualification_id: candidate.qualification_id ?? props.worker.pivot.shift_qualification_id,
                craft_abbreviation: candidate.craft_abbreviation ?? '',
            }
        )
        emit('replaced', {
            workers: data?.workers ?? null,
            shiftWorkerId: data?.shift_worker_id ?? null,
            removed: props.worker,
            replacement: candidate,
        })
        emit('close')
    } catch (error) {
        const errors = error?.response?.data?.errors
        submitError.value = (errors && Object.values(errors).flat()[0])
            || error?.response?.data?.message
            || t('Saving failed')
    } finally {
        submitting.value = false
    }
}

onMounted(loadCandidates)
</script>
