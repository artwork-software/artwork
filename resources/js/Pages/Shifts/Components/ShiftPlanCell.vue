<template>
    <div class="p-2 bg-gray-50/10 text-white text-xs rounded-lg shiftCell h-full cursor-pointer overflow-y-scroll hover:opacity-100" :class="hasMultiShiftGroups ? 'ring-2 ring-inset ring-rose-400' : ''">
        <div :class="[classes]">

            <template v-if="!checkIfUserHasVacationOnDay(user, day.withoutFormat)">
                <template v-for="shift in user.element?.shifts" :key="shift.id">
        <span v-if="shift.start_of_shift === day.fullDay">
          {{ shift.startPivot }} - {{ shift.endPivot }} {{ shift?.roomName }}
          <span v-if="shift.craftAbbreviation !== shift.craftAbbreviationUser && shift.craftAbbreviationUser">
            [{{ shift.craftAbbreviationUser }}]
          </span>,
        </span>
                </template>


                <template v-for="individual_time in user.individual_times" :key="individual_time.id">
                    <span v-if="individual_time.days_of_individual_time?.includes(day.withoutFormat)">
                          <span v-if="individual_time.start_time && individual_time.end_time">{{ individual_time.start_time }} - {{ individual_time.end_time }}</span>
                          <span v-else>{{ $t('All day') }}</span>
                        {{ individual_time.title }},
                    </span>
                </template>

                <span v-if="user.shift_comments[day.withoutFormat]">
                    {{ user.shift_comments[day.withoutFormat][0].comment }}
                </span>

                <template v-if="user.availabilities && user.availabilities[day.fullDay]">
                    <span v-for="availability in user.availabilities[day.fullDay]" :key="availability.id" class="text-green-500">
                      <span v-if="availability.comment">&bdquo;{{ availability.comment }}&rdquo; </span>
                    </span>
                </template>
            </template>

            <template v-else>
              <span class="h-full flex justify-center items-center text-[#f08b32]">
                {{ user.vacations.find(v => v.date === day.withoutFormat).type === 'OFF_WORK' ? $t('Day off work') : $t('not available') }}
              </span>
            </template>
        </div>
    </div>
    <!-- Rahmen dynamisch hinzufügen -->

</template>

<script setup>
import { computed } from 'vue'
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    user: { type: Object, required: true },
    day:  { type: Object, required: true },
    classes: { type: Array, default: () => [] },
})

const checkIfUserHasVacationOnDay = (user, day) =>
    user.vacations?.some(vacation => vacation.date === day)

/** Holt die ShiftGroup-ID robust aus allen gängigen Varianten */
function getShiftGroupId(shift) {
    // ✅ deine aktuellen Daten
    if (shift?.shiftGroup?.id != null) return shift.shiftGroup.id

    // weitere mögliche Varianten (Fallbacks)
    if (shift?.shift_group_id != null)   return shift.shift_group_id
    if (shift?.shiftGroupId != null)     return shift.shiftGroupId
    if (shift?.group_id != null)         return shift.group_id
    if (shift?.group?.id != null)        return shift.group.id
    if (shift?.shift_group?.id != null)  return shift.shift_group.id

    return null
}

/** Alle Schichten des Users am aktuellen Tag (robust auf beide Felder) */
const shiftsToday = computed(() => {
    const list = props.user?.element?.shifts ?? []
    const dayA = props.day.fullDay           // z.B. "13.10.2025"
    const dayB = props.day.withoutFormat     // z.B. "13.10.2025" (oder anderes Format)

    return list.filter(s => {
        const byStartOfShift =
            s.start_of_shift === dayA || s.start_of_shift === dayB

        const byDaysArray =
            Array.isArray(s.days_of_shift) &&
            (s.days_of_shift.includes(dayA) || s.days_of_shift.includes(dayB))

        return byStartOfShift || byDaysArray
    })
})

/** Rahmenregel: Mind. zwei unterschiedliche (nicht-leere) Gruppen am Tag */
const hasMultiShiftGroups = computed(() => {

    if(!usePage().props.warn_multiple_assignments){
        return false
    }

    const ids = new Set(
        shiftsToday.value
            .map(s => getShiftGroupId(s))
            .filter(id => id !== null && id !== undefined)
    )
    return ids.size >= 2
})
</script>
