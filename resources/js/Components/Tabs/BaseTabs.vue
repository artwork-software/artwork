<template>
    <div>
        <div class="sm:hidden">
            <label for="tabs" class="sr-only">Select a tab</label>
            <!-- Use an "onChange" listener to redirect the user to the selected tab URL. -->
            <select id="tabs" name="tabs" class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                <option v-for="tab in tabs" :key="tab.name" :selected="tab.current">{{ tab.name }}</option>
            </select>
        </div>
        <div class="hidden sm:block">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <div v-for="tab in tabs" :key="tab.name" @click="makeCurrent(tab.name)" :class="[tab.current ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium cursor-pointer']" :aria-current="tab.current ? 'page' : undefined">{{ tab.name }}</div>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>

const props = defineProps({
    tabs: {
        type: Array,
        required: true
    }
})

const makeCurrent = (tabName) => {
    props.tabs.forEach(tab => {
        tab.current = tab.name === tabName;
    });
}

</script>

<style scoped>

</style>
