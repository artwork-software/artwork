<template>
    <div class="my-2 flex items-start gap-x-4">
        <h1
            class="font-medium block subpixel-antialiased"
            :style="{ fontSize: data.data.title_size + 'px' }"
            :class="inSidebar ? 'text-white' : ' text-gray-900' "
        >
            {{ data.data.title }}
        </h1>
        <InfoButtonComponent :component="component" v-if="component" />
    </div>
</template>

<script setup>
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";

// Für DevTools
defineOptions({ name: "Title" });

const props = defineProps({
    data: { type: Object, required: true },
    inSidebar: { type: Boolean, default: false },
    component: { type: Object, default: null },
});

// Optional: wenn du magst, kannst du data direkt per Destructure nutzen
const { data, inSidebar, component } = props;
</script>
