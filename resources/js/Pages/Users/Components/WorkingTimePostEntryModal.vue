<template>
    <ArtworkBaseModal title="User Book working hours" description="Add or edit working hours for the user" @close="$emit('close')">
        <div v-if="bookingForm.user" class="flex items-center justify-between gap-4 rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2.5">
            <div class="flex min-w-0 items-center gap-3">
                <img :alt="bookingForm.user.first_name" :src="bookingForm.user.profile_photo_url" class="size-10 shrink-0 rounded-full object-cover">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-text">{{ bookingForm.user.first_name }} {{ bookingForm.user.last_name }}</p>
                    <p v-if="bookingForm.user.position" class="truncate text-xs text-text-subtle">{{ bookingForm.user.position }}</p>
                </div>
            </div>
            <button type="button" class="shrink-0 text-xs font-medium text-accent-600 hover:underline" @click="bookingForm.user = null">
                {{ $t('Select another user') }}
            </button>
        </div>

        <div v-else>
            <UserSearch @user-selected="selectUser" />
        </div>

        <form @submit.prevent="submit" class="mt-5 space-y-4">
            <div>
                <span class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Booking type') }}</span>
                <div class="inline-flex gap-[2px] rounded-[8px] bg-border-subtle p-[3px]" role="radiogroup" :aria-label="$t('Booking type')">
                    <button
                        v-for="option in bookingTypes"
                        :key="option.value"
                        type="button"
                        role="radio"
                        :aria-checked="bookingForm.plus_minus === option.value"
                        :class="[
                            bookingForm.plus_minus === option.value ? 'bg-surface shadow-raised text-text' : 'text-text hover:bg-white/60',
                            'inline-flex h-[26px] items-center gap-1.5 whitespace-nowrap rounded-[6px] px-3 text-[12.5px] font-semibold',
                            'transition-[background-color] duration-150 ease-out motion-reduce:transition-none',
                            'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-accent-600'
                        ]"
                        @click="bookingForm.plus_minus = option.value"
                    >
                        <PropertyIcon :name="option.icon" class="size-3.5" stroke-width="2" />
                        {{ $t(option.label) }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <BaseInput id="booking_hours" v-model="durationParts.hours" type="number" label="Hours" :min="0" :step="1" required />
                <BaseInput id="booking_minutes" v-model="durationParts.minutes" type="number" label="Minutes" :min="0" :max="59" :step="1" />
            </div>

            <div>
                <span class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Of which night hours') }}</span>
                <div class="grid grid-cols-2 gap-4">
                    <BaseInput id="booking_night_hours" v-model="nightParts.hours" type="number" label="Hours" :show-label="false" :placeholder="$t('Hours')" :min="0" :step="1" />
                    <BaseInput id="booking_night_minutes" v-model="nightParts.minutes" type="number" label="Minutes" :show-label="false" :placeholder="$t('Minutes')" :min="0" :max="59" :step="1" />
                </div>
            </div>

            <BaseInput id="booking_date" label="Date" type="date" v-model="bookingForm.date" required :error="bookingForm.errors.date" />

            <BaseTextarea id="booking_comment" v-model="bookingForm.comment" label="Comment" placeholder="Enter comment" required />

            <div
                class="rounded-lg px-3 py-2 text-xs"
                :class="feedbackError ? 'bg-danger-surface text-danger' : 'bg-surface-sunken text-text-muted'"
            >
                <template v-if="feedbackError">{{ feedbackError }}</template>
                <template v-else>
                    {{ $t('The time account will be changed by {0} hours.', [bookingPreview]) }}
                </template>
            </div>

            <div class="flex justify-center pt-1">
                <BaseUIButton :label="$t('Save')" is-add-button type="submit" :disabled="!canSubmit"/>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>

import {computed, reactive, watch} from "vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {useForm} from "@inertiajs/vue3";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import {useTranslation} from "@/Composeables/Translation.js";

const $t = useTranslation();

const props = defineProps({
    user: {
        type: Object,
        required: false
    }
})

const bookingForm = useForm({
    user: props.user ?? null,
    user_id: props.user ? props.user.id : null,
    hours: '',
    nightly_working_hours: '0:00',
    plus_minus: '+',
    comment: '',
    date: new Date().toISOString().split('T')[0] // Default to today
})

// Stunden und Minuten getrennt statt type="time": ein Zeitfeld endet bei 23:59,
// gebucht werden müssen aber auch größere Salden (z. B. Übernahme bei Produktivstart).
const durationParts = reactive({hours: '', minutes: ''})
const nightParts = reactive({hours: '', minutes: ''})

const bookingTypes = [
    {value: '+', label: 'Add hours', icon: 'IconPlus'},
    {value: '-', label: 'Deduct hours', icon: 'IconMinus'},
]

const toInt = (value) => {
    const parsed = Number.parseInt(value, 10)
    return Number.isFinite(parsed) ? parsed : 0
}

const isWholeNumber = (value) => value === '' || value === null || /^\d+$/.test(String(value))

const totalMinutes = computed(() => toInt(durationParts.hours) * 60 + toInt(durationParts.minutes))
const nightMinutes = computed(() => toInt(nightParts.hours) * 60 + toInt(nightParts.minutes))

const formatDuration = (minutes) => `${Math.floor(minutes / 60)}:${String(minutes % 60).padStart(2, '0')}`

const durationError = computed(() => {
    const parts = [durationParts.hours, durationParts.minutes, nightParts.hours, nightParts.minutes]
    if (!parts.every(isWholeNumber)) {
        return $t('Please enter whole numbers only.')
    }
    if (toInt(durationParts.minutes) > 59 || toInt(nightParts.minutes) > 59) {
        return $t('Minutes must be between 0 and 59.')
    }
    if (nightMinutes.value > totalMinutes.value) {
        return $t('The night hours cannot exceed the booked hours.')
    }
    return null
})

const serverError = computed(() => bookingForm.errors.hours
    ?? bookingForm.errors.nightly_working_hours
    ?? bookingForm.errors.comment
    ?? bookingForm.errors.user_id
    ?? null)

const feedbackError = computed(() => durationError.value ?? serverError.value)

const canSubmit = computed(() => !durationError.value
    && totalMinutes.value > 0
    && !!bookingForm.user
    && !bookingForm.processing)

// Serverfehler gelten nur für die abgeschickten Werte
watch([durationParts, nightParts], () => bookingForm.clearErrors('hours', 'nightly_working_hours'))

const bookingPreview = computed(() => `${bookingForm.plus_minus === '-' ? '−' : '+'}${formatDuration(totalMinutes.value)}`)

const emit = defineEmits(['close'])
const selectUser = (user) => {
    bookingForm.user = user;
}

const submit = () => {
    if (!canSubmit.value) {
        return;
    }
    bookingForm.clearErrors();
    bookingForm.user_id = bookingForm.user ? bookingForm.user.id : null;
    bookingForm.hours = formatDuration(totalMinutes.value);
    bookingForm.nightly_working_hours = formatDuration(nightMinutes.value);
    bookingForm.post(route('users.worktimes.store', bookingForm.user_id), {
        onSuccess: () => {
            emit('close');
        },
        preserveScroll: true,
        // bei Validierungsfehlern bleibt das Modal mit den Eingaben offen
        preserveState: 'errors',
    });
}
</script>
