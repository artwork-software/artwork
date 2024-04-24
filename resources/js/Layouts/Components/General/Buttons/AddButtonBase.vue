<script>
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import { PlusCircleIcon } from '@heroicons/vue/outline'
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "AddButtonBase",
    components: {BaseButton, PlusCircleIcon},
    mixins: [IconLib],
    props: {
        text: String,
        disabled: Boolean,
        horizontalPadding: String
    }
}
</script>

<template>
    <BaseButton :text="text" :disabled="disabled" :horizontalPadding="horizontalPadding">
        <IconCirclePlus stroke-width="1.5" class="h-4 w-4 mr-2" aria-hidden="true"/>
    </BaseButton>
</template>

<style scoped>

</style>
