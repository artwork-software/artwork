<template>
    <div class="flex items-center sticky top-[71px] gap-0.5 h-16 bg-artwork-navigation-background z-30 rounded-lg">
        <div :style="{minWidth: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px'}"></div>
        <div v-for="room in rooms" :key="room.id" :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', width: zoom_factor * 212 + 'px' }" class="flex items-center text-white line-clamp-2 mt-1">
            <AsyncSingleRoomInHeader :room="room" is-light />
        </div>
    </div>
</template>

<script setup>

import {usePage} from "@inertiajs/vue3";
import {defineAsyncComponent, ref} from "vue";

const zoom_factor = ref(usePage().props.auth.user.zoom_factor ?? 1);

const props = defineProps({
    rooms: {
        type: Object,
        required: true
    },
    filteredEventsLength: {
        type: Number,
        required: false,
        default: 0
    }
})

const AsyncSingleRoomInHeader = defineAsyncComponent(() =>
    import('@/Components/Calendar/Elements/SingleRoomInHeader.vue')
)

</script>

<style scoped>

</style>
