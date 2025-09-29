<template>
    <div>
        <div class="sm:hidden">
            <label for="tabs" class="sr-only">Select a tab</label>
            <!-- Mobile dropdown for navigation mode -->
            <select
                v-if="navigationMode === 'links'"
                id="tabs"
                name="tabs"
                class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                @change="handleMobileTabChange"
            >
                <option
                    v-for="tab in visibleTabs"
                    :key="tab.name"
                    :value="tab.href"
                    :selected="tab.current"
                >
                    {{ tab.name }}
                </option>
            </select>
            <!-- Mobile dropdown for local mode -->
            <select
                v-else
                id="tabs"
                name="tabs"
                class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                @change="handleLocalMobileTabChange"
            >
                <option v-for="tab in tabs" :key="tab.name" :selected="tab.current">{{ tab.name }}</option>
            </select>
        </div>
        <div class="hidden sm:block">
            <div class="border-b border-gray-200">
                <!-- Navigation mode with Link components (ToolSettings style) -->
                <nav v-if="navigationMode === 'links'" class="-mb-px uppercase text-xs tracking-wide flex space-x-8" aria-label="Tabs">
                    <Link
                        v-for="tab in visibleTabs"
                        :key="tab.name"
                        :href="tab.href"
                        :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                        :aria-current="tab.current ? 'page' : undefined"
                    >
                        {{ tab.name }}
                    </Link>
                </nav>
                <!-- Local mode with div elements (original style) -->
                <nav v-else class="-mb-px flex space-x-8" aria-label="Tabs">
                    <div
                        v-for="tab in tabs"
                        :key="tab.name"
                        @click="makeCurrent(tab.name)"
                        :class="[tab.current ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium cursor-pointer']"
                        :aria-current="tab.current ? 'page' : undefined"
                    >
                        {{ tab.name }}
                    </div>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    tabs: {
        type: Array,
        required: true
    },
    navigationMode: {
        type: String,
        default: 'local', // 'local' or 'links'
        validator: (value) => ['local', 'links'].includes(value)
    }
})

// Filter tabs based on permissions (only for navigation mode)
const visibleTabs = computed(() => {
    if (props.navigationMode !== 'links') {
        return props.tabs
    }

    return props.tabs.filter(tab => {
        // If hasPermission property doesn't exist, show the tab
        // If it exists and is true, show the tab
        // If it exists and is false, hide the tab
        return !tab.hasOwnProperty('hasPermission') || tab.hasPermission
    })
})

// Local tab switching (original functionality)
const makeCurrent = (tabName) => {
    props.tabs.forEach(tab => {
        tab.current = tab.name === tabName;
    });
}

// Handle mobile navigation for links mode
const handleMobileTabChange = (event) => {
    const selectedHref = event.target.value
    if (selectedHref) {
        router.visit(selectedHref)
    }
}

// Handle mobile navigation for local mode
const handleLocalMobileTabChange = (event) => {
    const selectedTabName = event.target.options[event.target.selectedIndex].text
    makeCurrent(selectedTabName)
}

</script>

<style scoped>

</style>
