<script>
import AddButtonBase from "@/Layouts/Components/General/Buttons/AddButtonBase.vue";

export default {
    name: "AddButtonSmall",
    components: {AddButtonBase},
    props: {
        text: String,
        disabled: Boolean,
    }
}
</script>

<template>
    <AddButtonBase :text="text" :disabled="disabled" horizontalPadding="px-4"/>
</template>

<style scoped>

</style>
