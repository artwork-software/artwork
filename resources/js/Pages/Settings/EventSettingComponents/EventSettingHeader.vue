<template>
    <div class=" ml-14 mt-5 mr-40">
        <div class="">
            <h2 class="headline1">{{$t('Event Settings')}}</h2>
            <div class="xsLight mt-2">
                {{ $t('Set global event settings.')}}
            </div>
        </div>

        <div class="w-full">
            <div class="mb-8">
                <div class="hidden sm:block">
                    <div class="border-gray-200">
                        <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                            <Link v-for="tab in tabs" :key="tab?.name"
                                  :href="tab.href"
                                  :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-semibold font-lexend']"
                                  :aria-current="tab.current ? 'page' : undefined" v-show="tab.show">
                                {{ $t(tab.name) }}
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <slot>

        </slot>

    </div>
</template>

<script setup>

import {Link} from "@inertiajs/vue3";

const props = defineProps({})


const tabs = [
    {
        name: 'Event Types',
        href: route('event_types.management'),
        current: route().current('event_types.management'),
        show: true
    },
    {
        name: 'Public holidays & school holidays',
        href: route('holiday.management'),
        current: route().current('holiday.management'),
        show: true
    },
    {
        name: 'Event Status',
        href: route('event_status.management'),
        current: route().current('event_status.management'),
        show: true
    },
    {
        name: 'Event Eigenschaften',
        href: route('event_settings.event_properties.index'),
        current: route().current('event_settings.event_properties.index'),
        show: true
    }
];
</script>

<style scoped>

</style>
