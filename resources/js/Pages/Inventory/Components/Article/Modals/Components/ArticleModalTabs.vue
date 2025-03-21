<template>
    <div class="border-gray-200 flex items-center justify-center">
        <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
            <div v-for="tab in tabs" v-show="tab.show" :key="tab?.id"
                 @click="updateTab(tab.id)"
                  :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-semibold cursor-pointer']"
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
    { id: 0, name: 'Images', current: true, show: true},
    { id: 1, name: 'Category & Properties', current: false, show: true},
    { id: 2, name: 'Detailed Quantity', current: false, show: isDetailedQuantity.value },
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
    tabs.value[2].show = value
})
</script>

<style scoped>

</style>