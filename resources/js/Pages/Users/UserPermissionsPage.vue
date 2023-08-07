<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="user_to_edit">
        <UserPermissionsTab :user_to_edit="user_to_edit" :permissions="permissions" :all_permissions="all_permissions"
                            :available_roles="available_roles"></UserPermissionsTab>
    </UserEditHeader>
    <BaseSidenav :show="show" @toggle="this.show =! this.show">
        <UserSidebar :user="user_to_edit" type="user" />
    </BaseSidenav>
</template>

<script>

import AppLayout from "@/Layouts/AppLayout.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import UserInfoTab from "@/Pages/Users/Tabs/UserInfoTab.vue";
import UserPermissionsTab from "@/Pages/Users/Tabs/UserPermissionsTab.vue";
import UserSidebar from "@/Pages/Users/Components/UserSidebar.vue";

export default {
    components: {
        UserSidebar,
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
    mounted() {
        this.show = true;
        setTimeout(() => {
            this.show = false;
        }, 1000)
    },
    methods: {}
}
</script>


<style scoped>

</style>
