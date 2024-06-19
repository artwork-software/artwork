<template>
    <div class="border-gray-200">
        <nav class="-mb-px uppercase text-xs tracking-wide flex space-x-8" aria-label="Tabs">
            <Link v-for="tab in tabs" v-show="tab.hasPermission" :href="tab.href" :key="tab.name"
                  :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                  :aria-current="tab.current ? 'page' : undefined">
                {{ tab.name }}
            </Link>
        </nav>
    </div>
</template>

<script>
import {defineComponent} from 'vue';
import {Link} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";

export default defineComponent({
    name: 'ToolSettingsTabs',
    mixins: [Permissions],
    components: {
        Link
    },
    data() {
        return {
            tabs: [
                {
                    name: this.$t('Branding'),
                    href: route('tool.branding'),
                    current: route().current('tool.branding'),
                    hasPermission: this.$can('change tool settings')
                },
                {
                    name: this.$t('Communication & Legal'),
                    href: route('tool.communication-and-legal'),
                    current: route().current('tool.communication-and-legal'),
                    hasPermission: this.$can('change tool settings')
                },
                {
                    name: this.$t('Interfaces'),
                    href: route('tool.interfaces'),
                    current: route().current('tool.interfaces'),
                    hasPermission: this.$can('change tool settings')
                }
            ]
        }
    }
})
</script>
