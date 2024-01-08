<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="user_to_edit">
        <UserPermissionsTab :user_to_edit="user_to_edit" :permissions="permissions" :all_permissions="all_permissions"
                            :available_roles="available_roles"></UserPermissionsTab>
    </UserEditHeader>
</template>

<script>

import AppLayout from "@/Layouts/AppLayout.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import UserPermissionsTab from "@/Pages/Users/Tabs/UserPermissionsTab.vue";

export default {
    components: {
        UserPermissionsTab,
        UserEditHeader,
        BaseSidenav,
        AppLayout
    },
    props: [
        'user_to_edit',
        'currentTab',
        'permissions',
        'all_permissions',
        'available_roles',
    ],
    data() {
        return {
            show: false,
        }
    },
    methods: {}
}
</script>


<style scoped>

</style>
