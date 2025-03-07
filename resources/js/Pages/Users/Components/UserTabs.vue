<template>
    <div>
        <div>
            <div class="border-gray-200">
                <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                    <Link v-for="tab in tabs" v-show="tab.hasPermission" :href="tab.href" :key="tab?.name"
                       :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-semibold']"
                       :aria-current="tab.current ? 'page' : undefined">
                        {{ $t(tab?.name) }}
                    </Link>
                </nav>
            </div>
        </div>
    </div>
</template>

<script>
import {defineComponent} from 'vue';
import {Link} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";

export default defineComponent({
    name: "UserTabs",
    mixins: [Permissions],
    components: {
        Link
    },
    data(){
        return {
            tabs: [
                {
                    name: 'Users',
                    href: route('users'),
                    current: route().current('users'),
                    hasPermission: true
                },
                {
                    name: 'Addresses',
                    href: route('users.addresses'),
                    current: route().current('users.addresses'),
                    hasPermission: true
                },
                {
                    name: 'Teams',
                    href: route('departments'),
                    current: route().current('departments'),
                    hasPermission: this.$can('teammanagement') || this.hasAdminRole()
                },
                {
                    name: 'Permission presets',
                    href: route('permission-presets.index'),
                    current: route().current('permission-presets.index'),
                    hasPermission: this.hasAdminRole()
                }
            ]
        }
    }
})
</script>



<style scoped>

</style>
