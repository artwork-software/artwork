<script>
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";

export default {
    name: "FormButton",
    components: {BaseButton},
    props: {
        text: String,
        disabled: Boolean,
        classes: String,
        type: {
            type: String,
            default: 'button'
        }
    }
}
</script>

<template>
    <BaseButton :classes="classes" :text="text" :type="type" :disabled="disabled" horizontalPadding="px-24" vertical-padding="py-4" class="font-semibold" />
</template>

<style scoped>

</style>
