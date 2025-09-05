<template>
    <div class="card-accent group relative rounded-2xl border border-zinc-200 bg-white/90 p-4 shadow-sm ring-1 ring-black/5">
        <!-- Kopfzeile: Meta + Löschen -->
        <div class="mb-3 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
        <span
            v-if="preset"
            class="inline-flex items-center gap-1 rounded-full border border-artwork-navigation-color/30 bg-artwork-navigation-color/10 px-2 py-0.5 text-[11px] font-medium text-artwork-buttons-hover ring-1 ring-inset ring-white/50"
        >
          {{ $t('Template') }}
        </span>

                <span
                    v-if="summary"
                    class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-zinc-50/80 px-2 py-0.5 text-[11px] text-zinc-700"
                    :title="$t('Summary')"
                >
          {{ summary }}
        </span>
            </div>

            <button
                type="button"
                class="invisible group-hover:visible rounded-full p-1 text-zinc-400 transition hover:bg-zinc-100 hover:text-red-600"
                @click="deleteTime"
                :aria-label="$t('Delete row')"
            >
                <XCircleIcon class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>

        <!-- Datum -->
        <div class="grid grid-cols-2 gap-3">
            <div class="relative">
                <label class="block mb-1 text-[11px] font-medium text-zinc-600">{{ $t('Start date*') }}</label>
                <span class="field-icon">
          <!-- calendar icon -->
          <svg viewBox="0 0 24 24" class="h-4 w-4"><path fill="currentColor" d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1m13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1zM5 7a1 1 0 0 0-1 1v2h16V8a1 1 0 0 0-1-1z"/></svg>
        </span>
                <input
                    type="date"
                    v-model="time.start_date"
                    @focusout="checkDates(time.start_date, time.end_date)"
                    :aria-invalid="hasDateError ? 'true' : 'false'"
                    :class="[
            'input-base pl-9',
            hasDateError ? 'input-error' : 'input-ok'
          ]"
                />
                <p v-if="showDatesNotGivenErrorText" class="mt-1 text-[11px] text-red-600">
                    {{ $t('Please fill in both fields.') }}
                </p>
            </div>

            <div class="relative">
                <label class="block mb-1 text-[11px] font-medium text-zinc-600">{{ $t('End date*') }}</label>
                <span class="field-icon">
          <svg viewBox="0 0 24 24" class="h-4 w-4"><path fill="currentColor" d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1m13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1zM5 7a1 1 0 0 0-1 1v2h16V8a1 1 0 0 0-1-1z"/></svg>
        </span>
                <input
                    type="date"
                    v-model="time.end_date"
                    @focusout="checkDates(time.start_date, time.end_date)"
                    :aria-invalid="hasDateError ? 'true' : 'false'"
                    :class="[
            'input-base pl-9',
            hasDateError ? 'input-error' : 'input-ok'
          ]"
                />
                <p v-if="showDatesStartGreaterThanEndText" class="mt-1 text-[11px] text-red-600">
                    {{ $t('The start time must be before the end time.') }}
                </p>
            </div>
        </div>

        <!-- Zeit -->
        <div class="mt-3 grid grid-cols-2 gap-3">
            <div class="relative">
                <label class="block mb-1 text-[11px] font-medium text-zinc-600">{{ $t('Start time*') }}</label>
                <span class="field-icon">
          <!-- clock icon -->
          <svg viewBox="0 0 24 24" class="h-4 w-4"><path fill="currentColor" d="M12 1.75a10.25 10.25 0 1 0 0 20.5a10.25 10.25 0 0 0 0-20.5M12 3.5a8.5 8.5 0 1 1 0 17a8.5 8.5 0 0 1 0-17m-.75 3.75v5.25l4.5 2.625l.75-1.237l-3.75-2.188V7.25z"/></svg>
        </span>
                <input
                    type="time"
                    v-model="time.start"
                    @focusout="checkTime(time.start, time.end)"
                    :aria-invalid="hasTimeError ? 'true' : 'false'"
                    :class="[
            'input-base pl-9',
            hasTimeError ? 'input-error' : 'input-ok'
          ]"
                />
                <p v-if="showTimesNotGivenErrorText" class="mt-1 text-[11px] text-red-600">
                    {{ $t('Please fill in both fields.') }}
                </p>
            </div>

            <div class="relative">
                <label class="block mb-1 text-[11px] font-medium text-zinc-600">{{ $t('End time*') }}</label>
                <span class="field-icon">
          <svg viewBox="0 0 24 24" class="h-4 w-4"><path fill="currentColor" d="M12 1.75a10.25 10.25 0 1 0 0 20.5a10.25 10.25 0 0 0 0-20.5M12 3.5a8.5 8.5 0 1 1 0 17a8.5 8.5 0 0 1 0-17m-.75 3.75v5.25l4.5 2.625l.75-1.237l-3.75-2.188V7.25z"/></svg>
        </span>
                <input
                    type="time"
                    v-model="time.end"
                    @focusout="checkTime(time.start, time.end)"
                    :aria-invalid="hasTimeError ? 'true' : 'false'"
                    :class="[
            'input-base pl-9',
            hasTimeError ? 'input-error' : 'input-ok'
          ]"
                />
                <p v-if="showTimesStartGreaterThanEndText" class="mt-1 text-[11px] text-red-600">
                    {{ $t('The start time must be before the end time.') }}
                </p>
            </div>
        </div>

        <!-- Kommentar -->
        <div class="mt-3">
            <label class="block mb-1 text-[11px] font-medium text-zinc-600">{{ $t('Comment') }}</label>
            <textarea
                v-model="time.description_without_html"
                rows="3"
                name="comment"
                id="comment"
                class="input-base input-ok min-h-[92px]"
                :placeholder="$t('Comment')"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { XCircleIcon } from '@heroicons/vue/solid'

const props = defineProps({
    time: { type: Object, required: true },
    preset: { type: Boolean, default: false }
})

const showDatesNotGivenErrorText = ref(false)
const showDatesStartGreaterThanEndText = ref(false)
const showTimesNotGivenErrorText = ref(false)
const showTimesStartGreaterThanEndText = ref(false)

const hasDateError = computed(() => showDatesNotGivenErrorText.value || showDatesStartGreaterThanEndText.value)
const hasTimeError = computed(() => showTimesNotGivenErrorText.value || showTimesStartGreaterThanEndText.value)

const checkDates = (startDate, endDate) => {
    const s = startDate ?? ''
    const e = endDate ?? ''
    showDatesNotGivenErrorText.value = s.length === 0 || e.length === 0
    // ISO YYYY-MM-DD ist lexikografisch vergleichbar
    showDatesStartGreaterThanEndText.value = !showDatesNotGivenErrorText.value && s > e
}

const checkTime = (start, end) => {
    const s = start ?? ''
    const e = end ?? ''
    showTimesNotGivenErrorText.value = s.length === 0 || e.length === 0
    // HH:MM ist lexikografisch vergleichbar
    showTimesStartGreaterThanEndText.value = !showTimesNotGivenErrorText.value && s > e
}

const deleteTime = () => {
    if (props.preset === true) {
        router.delete(route('preset.delete.timeline.row', props.time))
    } else {
        router.delete(route('delete.timeline.row', props.time), {
            preserveState: true,
            preserveScroll: true
        })
    }
}

// Schöne Zusammenfassung für den Chip (wenn alle Felder belegt)
const summary = computed(() => {
    const { start_date, end_date, start, end } = props.time || {}
    if (!start_date || !end_date || !start || !end) return ''
    return `${start_date} ${start} – ${end_date} ${end}`
})
</script>

<style scoped>
/* Akzentleiste links */
.card-accent::before{
    content:"";
    position:absolute;
    inset:0 auto 0 0;
    width:4px;
    border-top-left-radius:1rem;
    border-bottom-left-radius:1rem;
    background: linear-gradient(180deg,
    rgba(99,102,241,0.6), /* artwork-navigation-color approx. */
    rgba(16,185,129,0.25)); /* artwork-buttons-hover approx. */
}

/* Eingaben: Basis, Fokus, Fehler */
.input-base{
    width:100%;
    border-radius:0.75rem;        /* rounded-xl */
    border:1px solid rgb(228 228 231); /* zinc-200 */
    background:#fff;
    padding:0.5rem 0.75rem;
    font-size:0.875rem;           /* text-sm */
    outline: none;
    transition: box-shadow .2s, border-color .2s;
    box-shadow: inset 0 0 0 1px transparent;
}
.input-ok:focus{
    box-shadow: 0 0 0 2px rgba(59,130,246,.35); /* fallback focus */
}
.input-error{
    border-color: rgb(252 165 165);             /* red-300 */
    box-shadow: inset 0 0 0 1px rgba(248,113,113,.6);
}
.input-error:focus{
    box-shadow: 0 0 0 2px rgba(248,113,113,.5);
}

/* Icon im Feld */
.field-icon{
    position:absolute;
    left:0.625rem; /* ~2.5 */
    top:2.15rem;
    color: rgb(113 113 122); /* zinc-500 */
    pointer-events:none;
}

/* ruhige Zahlenbreite */
:global(.tabular-nums){ font-variant-numeric: tabular-nums; }
</style>
