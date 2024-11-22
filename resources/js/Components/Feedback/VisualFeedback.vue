<template>
    <transition
        enter-active-class="duration-300 ease-out"
        enter-from-class="transform opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="transform opacity-0"
    >
        <div class="my-3 text-xs px-3 py-1.5 text-white rounded-lg" :class="backgroundColor" v-show="showSaveSuccess">
            {{ $t('Saved. The changes have been successfully applied.') }}
        </div>
    </transition>
</template>

<script setup>

const props = defineProps({
    showSaveSuccess: {
        type: Boolean,
        default: false
    },
    backgroundColor: {
        type: String,
        default: 'bg-green-600'
    }
})

</script>

<style scoped>

</style>