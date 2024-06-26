<template>
    <BaseModal v-if="true" @closed="$emit('closed')">
        <h1 class="headline1">
            MultiEdit
        </h1>

        <div class="overflow-y-scroll">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <div v-for="tab in tabs" :key="tab.name" @click="makeCurrent(tab.name)" :class="[tab.current ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium cursor-pointer']" :aria-current="tab.current ? 'page' : undefined">{{ tab.name }}</div>
                </nav>
            </div>
        </div>
        <pre>
            {{ tabs }}
        </pre>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import {computed, ref} from "vue";
import BaseTabs from "@/Components/Tabs/BaseTabs.vue";

const props = defineProps({
    events: {
        type: Array,
        required: true
    },
    selectedItems: {
        type: Array,
        required: true
    },
})

const emits = defineEmits(['closed'])

const eventsWithSelectedItems = computed(() => {
    return props.events.map(event => {
        return {
            // ...event only id and name
            id: event.id,
            name: event.eventName ?? event.title,
            project: event.project,
            items: {
                // ...event.items only id and name
                ...props.selectedItems.map(item => {
                    return {
                        id: item.id,
                        name: item.name,
                        count: '0'
                    }
                })
            },
        }
    })
})
const tabs = ref([])

computed(() => {
    tabs.value = props.events.map(event => {
        return {
            name: event.name + ' (' + event.project.name + ')' ?? event.title,
            current: false
        }
    })
})


const makeCurrent = (tabName) => {
    tabs.value.forEach(tab => {
        tab.current = tab.name === tabName;
    });
    console.log(tabs.value);
}
//

</script>

<style scoped>

</style>