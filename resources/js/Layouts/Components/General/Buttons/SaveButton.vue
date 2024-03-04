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
    <BaseButton :text="$t('Save')" :disabled="disabled" horizontalPadding="px-24" vertical-padding="py-4" />
</template>

<style scoped>

</style>
