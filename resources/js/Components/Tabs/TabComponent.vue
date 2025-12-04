<template>
    <div class="font-lexend">
        <div class="mb-3">
            <div class="">
                <div class="border-gray-200">
                    <nav class="-mb-px text-sm tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                        <Link v-for="tab in tabs" :key="tab?.name"
                              :href="tab.href"
                              :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap pt-4 pb-2 px-1 border-b-2 font-semibold flex items-center gap-x-2']"
                              :aria-current="tab.current ? 'page' : undefined" v-show="tab.show">
                            <PropertyIcon :name="tab.icon" v-if="tab.icon" class="inline-block size-5" aria-hidden="true"/>
                            <span v-if="useTranslation">
                                {{ $t(tab.name) }}
                            </span>
                            <span v-else>
                                {{ tab.name }}
                            </span>
                        </Link>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import {Link} from "@inertiajs/vue3";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "TabComponent",
    components: {
        PropertyIcon,
        Link
    },
    props: {
        tabs: {
            type: Array,
            required: true
        },
        useTranslation: {
            type: Boolean,
            default: false
        }
    }
}
</script>


<style scoped>

</style>
