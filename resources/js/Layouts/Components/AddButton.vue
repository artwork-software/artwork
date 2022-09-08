<template>
    <button type="button"
            class="flex mt-2 ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
        <slot></slot>
    </button>
</template>

<script>
import Button from "@/Jetstream/Button";

export default {
    name: "AddButton",
    components: {Button}
}
</script>

<style scoped>

</style>
