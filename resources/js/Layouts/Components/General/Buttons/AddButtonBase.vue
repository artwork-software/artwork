<script>
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import { PlusCircleIcon } from '@heroicons/vue/outline'

export default {
    name: "AddButtonBase",
    components: {BaseButton, PlusCircleIcon},
    props: {
        text: String,
        disabled: Boolean,
        horizontalPadding: String
    }
}
</script>

<template>
    <BaseButton :text="text" :disabled="disabled" :horizontalPadding="horizontalPadding">
        <PlusCircleIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
    </BaseButton>
</template>

<style scoped>

</style>
