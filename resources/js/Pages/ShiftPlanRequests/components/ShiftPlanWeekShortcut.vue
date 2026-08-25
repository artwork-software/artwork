<template>
    <BaseUIButton
        v-if="can('can view shift plan') || is('artwork admin')"
        type="button"
        variant="secondary"
        icon="IconCalendarWeek"
        :label="$t('To shift plan (current week)')"
        :processing="processing"
        @click="goToCurrentWeek"
    />
</template>

<script setup>
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { can, is } from 'laravel-permission-to-vuejs';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';

const processing = ref(false);

// Lokales Datum formatieren — toISOString() wäre UTC und kippt abends auf den Vortag
const formatDate = (date) => [
    date.getFullYear(),
    String(date.getMonth() + 1).padStart(2, '0'),
    String(date.getDate()).padStart(2, '0'),
].join('-');

const goToCurrentWeek = async () => {
    if (processing.value) {
        return;
    }
    processing.value = true;

    const today = new Date();
    const dayOfWeek = today.getDay();
    const monday = new Date(today);
    monday.setDate(today.getDate() - (dayOfWeek === 0 ? 6 : dayOfWeek - 1));
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);

    const user = usePage().props.auth.user;

    try {
        await axios.patch(route('update.user.shift.calendar.filter.dates', user.id), {
            start_date: formatDate(monday),
            end_date: formatDate(sunday),
            isDailyView: !!user.shift_plan_daily_view,
        });
    } finally {
        router.visit(route('shifts.plan'));
    }
};
</script>
