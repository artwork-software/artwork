<template>
    <div class="inventory-tabs-container">
        <nav aria-label="Tabs">
            <Link v-for="tab in tabs" v-show="tab.hasPermission" :href="tab.href" :key="tab.name"
                  :class="[tab.current ? 'current' : 'not-current', 'default']"
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
    mixins: [Permissions],
    components: {
        Link
    },
    data() {
        return {
            tabs: [
                {
                    name: this.$t('Inventory'),
                    href: route('inventory-management.inventory'),
                    current: route().current('inventory-management.inventory'),
                    hasPermission: true
                },
                {
                    name: this.$t('Inventory Scheduling'),
                    href: route('inventory-management.scheduling'),
                    current: route().current('inventory-management.scheduling'),
                    hasPermission: true
                }
            ]
        }
    }
})
</script>
