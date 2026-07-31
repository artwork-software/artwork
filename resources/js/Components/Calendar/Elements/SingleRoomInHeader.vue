<template>
    <Link
        :style="colorStyle"
        class="flex font-semibold items-center px-3 min-w-0"
        :class="isLight ? 'text-white' : 'xsDark'"
        :href="route('rooms.show', { room: room?.id ?? room.roomId })"
        @mouseenter="onMouseEnter"
        @mouseleave="showTooltip = false"
    >
        <!-- Fixe Schriftgröße (unabhängig vom Zoom); lange Namen brechen auf max.
             2 Zeilen um, danach Ellipsis — voller Name per eigenem Hover-Tooltip
             (natives title wird auf dem Inertia-Link nicht zuverlässig angezeigt).
             whitespace-normal nötig, weil Eltern-Wrapper teils `truncate` (nowrap) setzen -->
        <span ref="nameEl" class="line-clamp-2 whitespace-normal break-words text-xs leading-tight">
            {{ roomName }}
        </span>
        <!-- Teleport liegt bewusst IM Link: die Komponente muss single-root bleiben,
             sonst erben durchgereichte Attrs + das data-v-Scoping der Eltern
             (.room-col-Spaltenbreite im CalendarHeader) nicht mehr auf den Link.
             Im DOM landet der Tooltip trotzdem am Body und entgeht so
             overflow-hidden/sticky der Kopfzeile; nur wenn der Name geclampt ist -->
        <Teleport to="body">
            <div
                v-if="showTooltip"
                class="fixed z-[100] max-w-xs p-2 text-xs leading-tight text-white bg-black rounded-md shadow-lg pointer-events-none -translate-x-1/2"
                :style="{ top: tooltipPos.top + 'px', left: tooltipPos.left + 'px' }"
            >
                {{ roomName }}
            </div>
        </Teleport>
    </Link>
</template>

<script setup>

import {Link} from "@inertiajs/vue3";
import {computed, ref} from "vue";
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

const roomName = computed(() => props.room?.name ?? props.room.roomName);

const nameEl = ref(null);
const showTooltip = ref(false);
const tooltipPos = ref({top: 0, left: 0});

const onMouseEnter = () => {
    const el = nameEl.value;
    // Tooltip nur, wenn line-clamp tatsächlich etwas abgeschnitten hat
    if (!el || el.scrollHeight <= el.clientHeight + 1) {
        return;
    }
    const rect = el.getBoundingClientRect();
    tooltipPos.value = {
        top: rect.bottom + 6,
        left: rect.left + rect.width / 2,
    };
    showTooltip.value = true;
};

const roomColor = computed(() => props.room?.roomColor ?? props.room?.color ?? null);

const colorStyle = computed(() => {
    // '#000000' ist der Migrations-Default für "keine Farbe gepflegt" — dann
    // keinen (schwarzen) Farbchip rendern.
    if (!roomColor.value || roomColor.value.toLowerCase() === '#000000') {
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
