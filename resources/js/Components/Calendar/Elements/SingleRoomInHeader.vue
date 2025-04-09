<template>
    <Link :style="textStyle" class="flex font-semibold w-full items-center px-8" :class="isLight ? 'text-white' : 'xsDark'" :href="route('rooms.show', { room: room?.id ?? room.roomId })">
        {{ room?.name ?? room.roomName }}
    </Link>
</template>

<script setup>

import {Link, usePage} from "@inertiajs/vue3";
import {computed, ref} from "vue";

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
