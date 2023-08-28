<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="user_to_edit">
        <UserInfoTab :user_to_edit="user_to_edit" :password_reset_status="password_reset_status"
                     :departments="departments"></UserInfoTab>
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
import UserSidebar from "@/Pages/Users/Components/UserSidebar.vue";

export default {
    components: {
        UserSidebar,
        UserInfoTab,
        UserEditHeader,
        BaseSidenav,
        AppLayout
    },
    props: [
        'user_to_edit',
        'currentTab',
        'password_reset_status',
        'departments',
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
