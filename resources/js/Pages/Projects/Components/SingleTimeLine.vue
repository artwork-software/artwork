<template>
    <div class="flex flex-row group">
        <div class="timeline-container">
            <div class="timeline-dates-container">
                <input class="timeline-date-input"
                       type="date"
                       v-model="time.start_date"
                       :placeholder="$t('Start*')"
                       @focusout="checkDates(time.start_date, time.end_date)"

                />
                <input class="timeline-date-input"
                       type="date"
                       v-model="time.end_date"
                       :placeholder="$t('Ende*')"
                       @focusout="checkDates(time.start_date, time.end_date)"/>
            </div>
            <span class="timeline-error-text" v-if="showDatesNotGivenErrorText">
                {{ $t('Please fill in both fields.') }}
            </span>
            <span class="timeline-error-text" v-if="showDatesStartGreaterThanEndText">
                {{ $t('The start time must be before the end time.') }}
            </span>
            <div class="timeline-times-container">
                <input class="timeline-time-input"
                       type="time"
                       v-model="time.start"
                       :placeholder="$t('Start*')"
                       @focusout="checkTime(time.start, time.end)"
                />
                <input class="timeline-time-input"
                       type="time"
                       :placeholder="$t('Ende*')"
                       v-model="time.end"
                       @focusout="checkTime(time.start, time.end)"/>
            </div>
            <span class="timeline-error-text" v-if="showTimesNotGivenErrorText">
                {{ $t('Please fill in both fields.') }}
            </span>
            <span class="timeline-error-text" v-if="showTimesStartGreaterThanEndText">
                {{ $t('The start time must be before the end time.') }}
            </span>
            <textarea class="timeline-textarea"
                      v-model="time.description_without_html"
                      rows="4"
                      :placeholder="$t('Comment')"
                      name="comment"
                      id="comment"
            />
        </div>
        <XCircleIcon class="group-hover:block ml-2 mt-2 delete-icon" @click="deleteTime"/>
    </div>
</template>

<script setup>
import {XCircleIcon} from "@heroicons/vue/solid";
import {ref} from "vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
        time: {
            type: Object,
            required: true
        },
        preset: {
            type: Boolean
        }
    }),
    showDatesNotGivenErrorText = ref(false),
    showDatesStartGreaterThanEndText = ref(false),
    showTimesNotGivenErrorText = ref(false),
    showTimesStartGreaterThanEndText = ref(false),
    checkDates = (startDate, endDate) => {
        showDatesNotGivenErrorText.value = startDate.length === 0 || endDate.length === 0;
        showDatesStartGreaterThanEndText.value = !showDatesNotGivenErrorText.value && startDate > endDate;
    },
    checkTime = (start, end) => {
        showTimesNotGivenErrorText.value = start.length === 0 || end.length === 0;
        showTimesStartGreaterThanEndText.value = !showTimesNotGivenErrorText.value && start > end;
    },
    deleteTime = () => {
        if (props.preset === true) {
            router.delete(route('preset.delete.timeline.row', props.time));
        } else {
            router.delete(
                route('delete.timeline.row', props.time),
                {
                    preserveState: true,
                    preserveScroll: true
                }
            )
        }
    };
</script>
