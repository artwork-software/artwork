<script>
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import Button from "@/Jetstream/Button.vue";

export default {
    name: "SecondaryButton",
    components: {Button, BaseButton},
    props: {
        disabled: Boolean,
        text: String
    }
}
</script>

<template>
    <BaseButton
        :text="text"
        :disabled="disabled"
        horizontalPadding="px-8"
        textColor="text-artwork-buttons-create"
        borderWidth="border-2"
        borderColor="border-artwork-buttons-create"
        backgroundColor="bg-transparent"
        class="hover:text-white"
    />
</template>

<style scoped>

</style>
