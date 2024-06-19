<script>
import {Link} from "@inertiajs/vue3";

export default {
    name: "Tabs",
    components: {Link},
    computed: {
        tabs() {
            return [
                {
                    name: this.$t('Rooms & areas'),
                    href: route('areas.management'),
                    current: route().current('areas.management'),
                    show: true
                },
                {
                    name: this.$t('Sort rooms'),
                    href: route('rooms.move'),
                    current: route().current('rooms.move'),
                    show: true
                },
            ]
        },
    },
}
</script>

<template>
    <div class="w-full">
        <div class="mb-3">
            <div class="hidden sm:block">
                <div class="border-gray-200">
                    <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                        <Link v-for="tab in tabs" :key="tab?.name"
                              :href="tab.href"
                              :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                              :aria-current="tab.current ? 'page' : undefined" v-show="tab.show">
                            {{ tab.name }}
                        </Link>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
