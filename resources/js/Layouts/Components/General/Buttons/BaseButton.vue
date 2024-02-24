<script>
import Button from "@/Jetstream/Button.vue";

export default {
    name: "BaseButton",
    components: {Button},
    props: {
        text: String,
        disabled: Boolean,
        horizontalPadding: String,
        textColor: {
            type: String,
            default: "text-white"
        },
        borderWidth: {
            type: String,
            default: "border"
        },
        borderColor: {
            type: String,
            default: "border-transparent"
        },
        backgroundColor: {
            type: String,
            default: "bg-buttonBlue"
        },
    }
}
</script>

<template>
    <button
       type="button"
       :disabled="disabled"
       :class="[disabled ? 'bg-secondary' : `${backgroundColor} hover:bg-buttonHover`,
       $props.horizontalPadding, textColor, borderWidth, borderColor]"
       class="flex py-2 mt-1 items-center rounded-full shadow-sm hover:shadow-blueButton focus:outline-none"
    >
        <!-- slot for the icon -->
        <slot></slot>
        <p class="text-sm">{{ text }}</p>
    </button>
</template>

<style scoped>

</style>
