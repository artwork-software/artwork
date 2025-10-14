<template>
    <!--<div class="border-gray-200">
        <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
            <Link v-for="tab in tabs" :key="tab?.name"
                  :href="tab.href"
                  :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-semibold']"
                  :aria-current="tab.current ? 'page' : undefined">
                {{ $t(tab.name) }}
            </Link>
        </nav>
    </div>-->
    <BaseTabs :tabs="tabs" />
</template>

<script setup>

import {Link} from "@inertiajs/vue3";
import BaseTabs from '@/Artwork/Tabs/BaseTabs.vue'

const props = defineProps({})


const tabs = [
    { name: 'Material issue book', href: route('issue-of-material.index'), current: route().current('issue-of-material.index'), permission: true, },
    { name: 'External Material issue book', href: route('extern-issue-of-material.index'), current: route().current('extern-issue-of-material.index'), permission: true, },
]
</script>

<style scoped>

</style>
