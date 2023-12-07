<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="user_to_edit">
        <UserInfoTab :user_to_edit="user_to_edit" :password_reset_status="password_reset_status"
                     :departments="departments"></UserInfoTab>
    </UserEditHeader>
</template>

<script>

import AppLayout from "@/Layouts/AppLayout.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import UserInfoTab from "@/Pages/Users/Tabs/UserInfoTab.vue";

export default {
    components: {
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
