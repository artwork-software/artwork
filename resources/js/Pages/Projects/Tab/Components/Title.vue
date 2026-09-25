<template>
    <!-- Mit Balkenfarbe: Abschnittsbalken (inkl. Abstand nach oben) zur optischen Gliederung des Tabs -->
    <div :class="hasBar ? 'mt-8 mb-3' : 'my-2'">
        <div
            v-if="hasBar"
            class="flex items-center justify-between gap-x-4 rounded-md px-4 py-2.5"
            :style="{ backgroundColor: barColor, color: barTextColor }"
        >
            <h2 class="font-semibold subpixel-antialiased" :style="{ fontSize: titleSize + 'px' }">
                {{ data.data.title }}
            </h2>
            <InfoButtonComponent :component="component" v-if="component" />
        </div>
        <div v-else class="flex items-start gap-x-4">
            <h1
                class="font-medium block subpixel-antialiased"
                :style="{ fontSize: titleSize + 'px' }"
                :class="inSidebar ? 'text-white' : ' text-text' "
            >
                {{ data.data.title }}
            </h1>
            <InfoButtonComponent :component="component" v-if="component" />
        </div>
        <p
            v-if="data.data.subtitle"
            class="mt-1.5 whitespace-pre-line text-xs text-text-muted"
            :class="{ '!text-white/70': inSidebar }"
        >{{ data.data.subtitle }}</p>
    </div>
</template>

<script setup>
import { computed } from "vue";
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
import { useColorHelper } from "@/Composeables/UseColorHelper.js";

// Für DevTools
defineOptions({ name: "Title" });

const props = defineProps({
    data: { type: Object, required: true },
    inSidebar: { type: Boolean, default: false },
    component: { type: Object, default: null },
});

const { getTextColorBasedOnBackground } = useColorHelper();

const barColor = computed(() => props.data.data?.bar_color || '');
const hasBar = computed(() => barColor.value !== '');
const barTextColor = computed(() => getTextColorBasedOnBackground(barColor.value));
const titleSize = computed(() => Number(props.data.data?.title_size) || 16);
</script>
