<template>
    <div :class="classes">
        <template v-if="!checkIfUserHasVacationOnDay(user, day.withoutFormat)">
            <div v-for="shift in user.element?.shifts" :key="shift.id">
                <span v-if="shift.start_of_shift === day.fullDay">
                    {{ shift.start }} - {{ shift.end }} {{ shift?.roomName }}
                    <span v-if="shift.craftAbbreviation !== shift.craftAbbreviationUser && shift.craftAbbreviationUser">
                        [{{ shift.craftAbbreviationUser }}]
                    </span>,
                </span>
            </div>

            <div v-for="individual_time in user.individual_times" :key="individual_time.id">
                <span v-if="individual_time.days_of_individual_time?.includes(day.withoutFormat)">
                    <span v-if="individual_time.start_time && individual_time.end_time">
                        {{ individual_time.start_time }} - {{ individual_time.end_time }}
                    </span>
                    <span v-else>
                        {{ $t('All day') }}
                    </span>
                    {{ individual_time.title }},
                </span>
            </div>
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

</template>

<script setup>
const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    day: {
        type: Object,
        required: true,
    },
    classes: {
        type: Array,
        default: () => [],
    },
})

const checkIfUserHasVacationOnDay = (user, day) => user.vacations?.some(vacation => vacation.date === day)
</script>

<style scoped>
</style>
