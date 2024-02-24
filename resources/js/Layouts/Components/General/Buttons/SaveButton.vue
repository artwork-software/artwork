<script>
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";

export default {
    name: "SaveButton",
    components: {BaseButton},
    props: {
        disabled: Boolean
    }
}
</script>

<template>
    <BaseButton text="Speichern" :disabled="disabled" horizontalPadding="px-24" />
</template>

<style scoped>

</style>
