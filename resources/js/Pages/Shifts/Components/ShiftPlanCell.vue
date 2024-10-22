<template>
    <div :class="classes">
        <div v-for="shift in user.element?.shifts" v-if="!checkIfUserHasVacationOnDay(user, day.without_format)">
        <span v-if="shift.days_of_shift?.includes(day.full_day)">
            {{ shift.start }} - {{ shift.end }} {{ shift.roomName }}
            <span
                v-if="shift.craftAbbreviation !== shift.craftAbbreviationUser && shift.craftAbbreviationUser !== null">
                [{{ shift.craftAbbreviationUser }}]
            </span>,
        </span>
        </div>
        <div v-for="individual_time in user.individual_times" v-if="!user.vacations?.some(vacation => vacation.date === day.without_format)">
        <span v-if="individual_time.days_of_individual_time?.includes(day.without_format)">
            <span
                v-if="individual_time.start_time && individual_time.end_time">
                {{ individual_time.start_time }} - {{ individual_time.end_time }}
            </span>
            <span v-else>
                {{ $t('All day') }}
            </span>
            {{ individual_time.title }},
        </span>
        </div>
        <span v-else class="h-full flex justify-center items-center text-[#f08b32]">
        {{ user.vacations?.find(v => v.date === day.without_format).type === 'OFF_WORK' ? $t('Day off work') : $t('not available') }}
    </span>
        <span v-if="user.shift_comments[day.without_format]">
        {{ user.shift_comments[day.without_format][0].comment }}
    </span>
        <span v-if="user.availabilities">
        <span v-for="availability in user.availabilities[day.full_day]">
            <span class="text-green-500">
                <span v-if="availability.comment">&bdquo;{{ availability.comment }}&rdquo; </span>
            </span>
        </span>
    </span>
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

const checkIfUserHasVacationOnDay = (user, day) => {
    return user.vacations?.some(vacation => vacation.date === day)
}

</script>

<style scoped>

</style>