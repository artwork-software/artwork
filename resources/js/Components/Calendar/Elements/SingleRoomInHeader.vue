<template>
    <Link :style="[textStyle, colorStyle]" class="flex font-semibold items-center px-8" :class="isLight ? 'text-white' : 'xsDark'" :href="route('rooms.show', { room: room?.id ?? room.roomId })">
        {{ room?.name ?? room.roomName }}
    </Link>
</template>

<script setup>

import {Link, usePage} from "@inertiajs/vue3";
import {computed, ref} from "vue";
import {useColorHelper} from "@/Composeables/UseColorHelper.js";

const zoom_factor = ref(usePage().props.auth.user.zoom_factor ?? 1);

const props = defineProps({
    room: {
        type: Object,
        required: true
    },
    isLight: {
        type: Boolean,
        required: false,
        default: false
    }
})

const {getTextColorBasedOnBackground} = useColorHelper();

const roomColor = computed(() => props.room?.roomColor ?? props.room?.color ?? null);

const colorStyle = computed(() => {
    if (!roomColor.value) {
        return {};
    }
    const r = parseInt(roomColor.value.slice(-6, -4), 16);
    const g = parseInt(roomColor.value.slice(-4, -2), 16);
    const b = parseInt(roomColor.value.slice(-2), 16);
    return {
        backgroundColor: roomColor.value,
        color: getTextColorBasedOnBackground(`rgb(${r}, ${g}, ${b})`),
        borderRadius: '0.5rem',
        paddingTop: '0.375rem',
        paddingBottom: '0.375rem',
    };
});

const textStyle = computed(() => {
    const fontSize = `max(calc(${zoom_factor.value} * 0.875rem), 10px)`;
    const lineHeight = `max(calc(${zoom_factor.value} * 1.25rem), 1.3)`;
    return {
        fontSize,
        lineHeight,
    };
})
</script>

<style scoped>

</style>
