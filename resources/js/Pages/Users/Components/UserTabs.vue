<template>
    <BaseTabs :tabs="tabs" />
</template>

<script>
import {defineComponent} from 'vue';
import {Link} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";

export default defineComponent({
    name: "UserTabs",
    mixins: [Permissions],
    components: {
        BaseTabs,
        Link
    },
    data(){
        return {
            tabs: [
                {
                    name: 'Users',
                    href: route('users'),
                    current: route().current('users'),
                    permission: true,
                    icon: 'IconUsers'
                },
                {
                    name: 'Freelancers & Service Providers',
                    href: route('users.addresses'),
                    current: route().current('users.addresses'),
                    permission: true,
                    icon: 'IconCompass'
                },
                {
                    name: 'Manufacturers',
                    href: route('manufacturers.index'),
                    current: route().current('manufacturers.index'),
                    permission: true,
                    icon: 'IconBuildingWarehouse'
                },
                {
                    name: 'Accommodations',
                    href: route('accommodation.index'),
                    current: route().current('accommodation.index'),
                    permission: true,
                    icon: 'IconHome'
                },
                {
                    name: 'Teams',
                    href: route('departments'),
                    current: route().current('departments'),
                    permission: this.$can('teammanagement') || this.hasAdminRole(),
                    icon: 'IconUsersGroup'
                },
                {
                    name: 'Permission presets',
                    href: route('permission-presets.index'),
                    current: route().current('permission-presets.index'),
                    permission: this.hasAdminRole(),
                    icon: 'IconLicense'
                }
            ]
        }
    }
})
</script>



<style scoped>

</style>
