<template>
    <div>
        <div v-if="allAttributes.length > 0" class="flex items-center flex-wrap gap-1">
            <div
                v-for="(attribute, index) in visibleAttributes"
                :key="index"
                class="inline-flex items-center rounded-full border border-gray-100 pr-2"
            >
                <div class="inline-block size-5 rounded-full" :style="{ backgroundColor: attribute.color }" />
                <span class="ml-1 text-xs text-secondary truncate max-w-[6rem]">{{ attribute.name }}</span>
            </div>
            <div v-if="overflowAttributes.length > 0" class="relative" @click.stop>
                <button
                    type="button"
                    class="inline-flex items-center justify-center size-6 rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600 hover:bg-zinc-200 transition"
                    @click="showOverflow = !showOverflow"
                >
                    +{{ overflowAttributes.length }}
                </button>
                <div
                    v-if="showOverflow"
                    class="absolute z-50 mt-1 left-0 bg-white border border-zinc-200 rounded-lg shadow-lg p-3 min-w-[14rem] max-w-[20rem]"
                >
                    <div class="flex flex-wrap gap-1.5">
                        <div
                            v-for="(attr, i) in overflowAttributes"
                            :key="i"
                            class="inline-flex items-center rounded-full border border-gray-100 pr-2"
                        >
                            <div class="inline-block size-5 rounded-full" :style="{ backgroundColor: attr.color }" />
                            <span class="ml-1 text-xs text-secondary">{{ attr.name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, ref } from "vue";

const MAX_VISIBLE = 2;

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: false,
    },
});

const showOverflow = ref(false);

const allAttributes = computed(() => {
    if (!props.project.attributes) return [];
    const attrs = [];
    for (const category of Object.values(props.project.attributes)) {
        if (Array.isArray(category)) {
            attrs.push(...category);
        }
    }
    return attrs;
});

const visibleAttributes = computed(() => allAttributes.value.slice(0, MAX_VISIBLE));
const overflowAttributes = computed(() => allAttributes.value.slice(MAX_VISIBLE));
</script>
