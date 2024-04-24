<script>
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "PlusButton",
    mixins: [IconLib],
}
</script>

<template>
    <button type="button" class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover rounded-full p-1 flex items-center justify-center transition-all duration-150 ease-in-out">
        <IconCirclePlus class="h-8 w-8 text-white" stroke-width="2.5"/>
    </button>
</template>

<style scoped>

</style>
