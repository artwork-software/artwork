<script>
import Button from "@/Jetstream/Button.vue";

export default {
    name: "BaseButton",
    components: {Button},
    props: {
        text: String,
        disabled: Boolean,
        horizontalPadding: {
            type: String,
            default: "px-4"
        },
        verticalPadding: {
            type: String,
            default: "py-2"
        },
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
            default: "bg-artwork-buttons-create"
        },
        classes: {
            type: String,
            default: ""
        }
    }
}
</script>

<template>
    <button
       type="button"
       :disabled="disabled"
       :class="[disabled ? 'bg-secondary' : `${backgroundColor} hover:bg-artwork-buttons-hover`,
       $props.horizontalPadding, textColor, borderWidth, borderColor, verticalPadding, classes]"
       class="flex items-center rounded-lg shadow-sm focus:outline-none transition-all duration-150 ease-in-out "
    >
        <!-- slot for the icon -->
        <slot></slot>
        <p class="text-sm">{{ text }}</p>
    </button>
</template>

<style scoped>

</style>
