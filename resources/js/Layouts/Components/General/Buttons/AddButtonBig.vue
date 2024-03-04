<script>
import AddButtonBase from "@/Layouts/Components/General/Buttons/AddButtonBase.vue";

export default {
    name: "AddButtonBig",
    components: {AddButtonBase},
    props: {
        text: String,
        disabled: Boolean,
    }
}
</script>

<template>
    <AddButtonBase :text="text" horizontalPadding="px-16"/>
</template>

<style scoped>

</style>
