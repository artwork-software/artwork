<template>
    <AppLayout :title="$t('Event Verifications')">
        <div class="mx-auto container px-4 sm:px-6 lg:px-8 mt-5 pb-10">

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
import {usePage} from "@inertiajs/vue3";
import {computed} from "vue";
import {incomingRequestCount, loadIncomingRequestCount} from "@/Composeables/incomingRequestCount.js";

// Zahl offener Anfragen am Reiter; die Eingangsseite setzt sie aus ihren Listen, sonst wird nachgeladen
if (usePage().props.canSeeIncomingRequests) {
    loadIncomingRequestCount()
}

const tabs = computed(() => [
    {
        name: 'Incoming requests',
        href: route('event-verifications.index'),
        current: route().current('event-verifications.index'),
        permission: usePage().props.canSeeIncomingRequests,
        count: incomingRequestCount.value,
    },
    {
        name: 'Requests sent by me',
        href: route('event-verifications.sent'),
        current: route().current('event-verifications.sent'),
        permission: true,
    },
])

</script>

<style scoped>

</style>
