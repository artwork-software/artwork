<script setup>
const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    description: {
        type: String,
        default: ''
    },
    whiteText: {
        type: Boolean,
        default: false
    }
})
</script>

<template>
    <div class="">
        <h3 class="font-medium text-lg subpixel-antialiased">{{ $t(props.title) }}</h3>
        <p class="text-xs text-zinc-500 subpixel-antialiased mt-0.5">
            {{ $t(props.description) }}
        </p>
    </div>
    <!--<div class="hidden">
        <h2 class="text-lg/6 font-lexend font-bold" :aria-label="$t(title)" :class="whiteText ? 'text-white' : 'text-gray-900'">
            {{ $t(title) }}
        </h2>
        <p v-if="description" class="mt-0.5 text-sm font-medium" :aria-description="$t(description)" :class="whiteText ? 'text-gray-300' : 'text-zinc-500'">
            {{ $t(description) }}
        </p>
    </div>-->
</template>

<style scoped>

</style>
