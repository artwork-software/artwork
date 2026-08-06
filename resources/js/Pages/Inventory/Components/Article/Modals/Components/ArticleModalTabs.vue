<template>
    <div class="border-border-subtle flex items-center justify-center">
        <nav class="-mb-px uppercase text-xs tracking-wide flex space-x-8" aria-label="Tabs">
            <div v-for="tab in tabs" v-show="tab.show" :key="tab?.id"
                 @click="updateTab(tab.id)"
                  :class="[tab.current ? 'border-accent-600 text-accent-600' : 'border-transparent text-text-subtle hover:text-text-muted hover:border-border', 'whitespace-nowrap py-4 px-1 border-b-2 font-semibold cursor-pointer']"
                  :aria-current="tab.current ? 'page' : undefined">
                {{ $t(tab?.name) }}
            </div>
        </nav>
    </div>
</template>

<script setup>

import {ref, watch} from "vue";

const props = defineProps({
    isDetailedQuantity: {
        type: Boolean,
        required: false,
        default: false
    }
})

const isDetailedQuantity = ref(props.isDetailedQuantity)

const tabs = ref([
    { id: 0, name: 'Category & Properties', current: true, show: true},
    { id: 1, name: 'Detailed Quantity', current: false, show: isDetailedQuantity.value },
])

const updateTab = (tabId) => {
    tabs.value.forEach(tab => {
        tab.current = tab.id === tabId
    })

    emits('update:currentTab', tabId)
}

const emits = defineEmits(['update:currentTab'])


// watch on props.isDetailedQuantity
watch(() => props.isDetailedQuantity, (value) => {
    isDetailedQuantity.value = value
    tabs.value[1].show = value
})
</script>

<style scoped>

</style>