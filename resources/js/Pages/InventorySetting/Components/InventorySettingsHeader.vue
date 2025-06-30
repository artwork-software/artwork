<template>
    <AppLayout :title="$t('Inventory')">
        <div class="artwork-container">
            <div class="container">
                <div class="">
                    <h2 class="headline1">{{$t('Inventory')}}</h2>
                    <div class="xsLight mt-2">
                        {{$t('Define global settings for inventory planning.')}}
                    </div>
                </div>

                <div class="border-gray-200 mb-5">
                    <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                        <Link v-for="tab in tabs" :key="tab?.name"
                              :href="tab.href"
                              :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-semibold']"
                              :aria-current="tab.current ? 'page' : undefined">
                            {{ $t(tab.name) }}
                        </Link>
                    </nav>
                </div>

                <slot>

                </slot>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import {Link} from "@inertiajs/vue3";

const props = defineProps({})


const tabs = [
    { name: 'Categories & Sub-Categories', href: route('inventory-management.settings.category'), current: route().current('inventory-management.settings.category') },
    { name: 'Properties', href: route('inventory-management.settings.properties'), current: route().current('inventory-management.settings.properties') },
]
</script>

<style scoped>

</style>
