<script>
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";

export default {
    name: "FormButton",
    components: {BaseButton},
    props: {
        text: String,
        disabled: Boolean,
        classes: String
    }
}
</script>

<template>
    <BaseButton :classes="classes" :text="text" :disabled="disabled" horizontalPadding="px-24" vertical-padding="py-4" class="font-semibold" />
</template>

<style scoped>

</style>
