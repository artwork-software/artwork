<template>
    <div class="rounded-2xl border border-border-subtle bg-white">
        <button
            type="button"
            class="flex w-full items-center justify-between px-5 py-4 text-left"
            @click="open = !open"
        >
            <span class="text-sm font-semibold text-text">{{ label }}</span>
            <span class="text-text-subtle">{{ open ? '−' : '+' }}</span>
        </button>

        <div v-show="open" class="border-t border-border-subtle px-5 py-4">
            <div v-if="children.length === 0" class="text-xs text-text-subtle">
                {{ $t('No content') }}
            </div>
            <div v-else class="space-y-4">
                <ExternalComponentRenderer
                    v-for="child in children"
                    :key="child.component_id"
                    :component="child"
                    :project-id="projectId"
                    :tab-id="tabId"
                    :scope="scope"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import ExternalComponentRenderer from '@/Pages/ExternalAccess/Project/Components/ExternalComponentRenderer.vue'

const props = defineProps({
    component: { type: Object, required: true },
    projectId: { type: Number, required: true },
    tabId: { type: Number, required: true },
    scope: { type: Object, required: true },
})

const schema = computed(() => ({ label: '', ...(props.component.data_schema || {}) }))
const label = computed(() => schema.value.label || props.component.name)
const children = computed(() => props.component.children || [])
const open = ref(true)
</script>
