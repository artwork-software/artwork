<template>
    <div class="modal-header" :class="classes">
        <div :class="fullModal ? 'full-modal' : 'not-full-modal'">
            <div class="model-title">
                {{ title }}
            </div>
            <p class="mb-3 text-gray-400 text-sm" v-if="subTitle">
                {{ subTitle }}
            </p>
            <p class="modal-description" v-if="description">
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