<template>
    <Link :style="colorStyle" class="flex font-semibold items-center px-3 min-w-0" :class="isLight ? 'text-white' : 'xsDark'" :href="route('rooms.show', { room: room?.id ?? room.roomId })">
        <!-- Fixe Schriftgröße (unabhängig vom Zoom); zu lange Namen werden abgekürzt -->
        <span class="truncate text-sm" :title="room?.name ?? room.roomName">
            {{ room?.name ?? room.roomName }}
        </span>
    </Link>
</template>

<script setup>

import {Link} from "@inertiajs/vue3";
import {computed} from "vue";
import {useColorHelper} from "@/Composeables/UseColorHelper.js";

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
        paddingTop: '0.25rem',
        paddingBottom: '0.25rem',
    };
});
</script>

<style scoped>

</style>
