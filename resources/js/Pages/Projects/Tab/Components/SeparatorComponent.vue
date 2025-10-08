<template>
    <div
        :style="{
      paddingTop: calculateHeight + 'px',
      paddingBottom: calculateHeight + 'px',
    }"
        class="w-full flex items-center justify-center"
    >
        <div
            v-if="data.data.showLine"
            class="w-full flex items-center justify-center h-0.5 bg-zinc-300"
        ></div>
    </div>
</template>

<script setup>
import { computed } from "vue";

// Für DevTools
defineOptions({ name: "SeparatorComponent" });

const props = defineProps({
    data: { type: Object, required: true },
    projectId: { type: [String, Number], required: false },
    inSidebar: { type: Boolean, default: false },
});

// Höhe berechnen (identisch zu deiner computed-Property)
const calculateHeight = computed(() => {
    return parseInt(props.data.data.height) / 2;
});
</script>

<style scoped>
</style>
