<template>
    <AppLayout>
        <div class="mx-auto container px-4 sm:px-6 lg:px-8 mt-5 max-h-screen">

            <slot name="header"></slot>


            <BaseTabs :tabs="tabs" />

            <div class="py-4">
                <slot name="body"></slot>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import {can, is} from "laravel-permission-to-vuejs";

const props = defineProps({

})

const tabs = [
    { name: 'My Verification Requests', href: route('event-verifications.index'), current: route().current('event-verifications.index'), permission: ('can see planning calendar | can edit planning calendar') || is('artwork admin')},
    { name: 'Verification Requests', href: route('event-verifications.requests'), current: route().current('event-verifications.requests'), permission: can('can edit planning calendar') || is('artwork admin') },
]

</script>

<style scoped>

</style>