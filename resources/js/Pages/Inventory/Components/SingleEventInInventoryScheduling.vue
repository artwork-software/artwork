<template>
   <div class="text-sm">
       <div class="py-1 px-2 border" :style="{
        backgroundColor: backgroundColorWithOpacity(event.event_type.hex_code),
        color: textColorWithDarken(event.event_type.hex_code),
        border: textColorWithDarken(event.event_type.hex_code)
        }"
        :class="isLastEvent ? 'rounded-b-lg' : ''">
           <div>
               {{ event.eventName ?? event.title }}
           </div>
       </div>
   </div>
</template>

<script setup>

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    isLastEvent: {
        type: Boolean,
        required: false,
        default: false
    }
})


const backgroundColorWithOpacity = (color, percent = 15) => {
    if (!color) return `rgb(255, 255, 255, ${percent}%)`;
    return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent}%)`;
}
const textColorWithDarken = (color, percent = 75) => {
    if (!color) return 'rgb(180, 180, 180)';
    return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
}

</script>

<style scoped>

</style>