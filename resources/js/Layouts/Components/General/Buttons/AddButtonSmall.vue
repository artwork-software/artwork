<script>
import AddButtonBase from "@/Layouts/Components/General/Buttons/AddButtonBase.vue";

export default {
    name: "AddButtonSmall",
    components: {AddButtonBase},
    props: {
        text: String,
        disabled: Boolean,
        noIcon: {
            type: Boolean,
            default: false
        },
        type: String
    }
}
</script>

<template>
    <AddButtonBase :type="type" :text="text" :no-icon="noIcon" :disabled="disabled" horizontalPadding="px-4"/>
</template>

<style scoped>

</style>
