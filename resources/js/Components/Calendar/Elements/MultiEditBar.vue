<template>
    <div class="fixed bottom-0 w-full h-28 bg-artwork-navigation-background/30 z-100 pointer-events-none">
        <div class="flex items-center justify-center h-full gap-4">
            <div>
                <FormButton :disabled="countOfSelectedEvents === 0" :text="countOfSelectedEvents + ' Termin(e) verschieben'" class="transition-all duration-300 ease-in-out pointer-events-auto"/>
            </div>
            <div>
                <FormButton class="bg-artwork-error hover:bg-artwork-error/70 transition-all duration-300 ease-in-out pointer-events-auto" @click="$emit('closeMultiEdit')" :text="countOfSelectedEvents + ' ' + $t('Delete events')"/>
            </div>
        </div>

    </div>
</template>

<script setup>

import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const emit = defineEmits(['closeMultiEdit'])

const props = defineProps({
    countOfSelectedEvents: {
        type: Number,
        required: false,
        default: 0
    }
})

</script>

<style scoped>

</style>
