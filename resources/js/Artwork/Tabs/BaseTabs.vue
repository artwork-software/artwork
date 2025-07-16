<template>
    <div>
        <div class="block font-lexend overflow-scroll">
            <div class=" border-gray-200">
                <nav class="-mb-px flex space-x-10 " aria-label="Tabs">
                    <div v-for="tab in tabs">
                        <Link v-if="tab.permission" :key="tab.name" :href="tab.href" :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-gray-500 hover:border-gray-200 hover:text-gray-700', 'flex border-b-2 px-1 py-4 items-center gap-x-2 text-sm font-medium whitespace-nowrap font-lexend']" :aria-current="tab.current ? 'page' : undefined">
                            <component :is="tab.icon" v-if="tab.icon" class="inline-block size-5" aria-hidden="true"/>
                            {{ $t(tab.name) }}
                            <span v-if="tab.count" :class="[tab.current ? 'bg-artwork-buttons-create/10 text-artwork-buttons-create' : 'bg-gray-100 text-gray-900', 'ml-3 hidden rounded-full px-2.5 py-0.5 text-xs font-medium md:inline-block']">{{ tab.count }}</span>
                        </Link>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import {usePage, Link, router} from "@inertiajs/vue3";
const props = defineProps({
    tabs: {
        type: Array,
        required: true
    }
})

</script>

<style scoped>

</style>
