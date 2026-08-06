<template>
    <div class="mb-6 text-left" :class="classes">
        <div :class="fullModal ? 'px-6 pt-4 pb-2' : 'pt-4 pb-2'">
            <h2 class="font-lexend font-semibold text-lg text-text">
                {{ title }}
            </h2>
            <p class="mb-3 text-[13px] text-text-muted" v-if="subTitle">
                {{ subTitle }}
            </p>
            <p class="text-[13px] text-text-muted" v-if="description">
                {{ description }}
            </p>
        </div>
    </div>
</template>

<script setup>

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    subTitle: {
        type: String,
        required: false,
        default: ''
    },
    description: {
        type: String,
        required: false,
        default: ''
    },
    fullModal: {
        type: Boolean,
        default: false
    },
    classes: {
        type: String,
        default: ''
    }
})

</script>

<style scoped>

</style>
