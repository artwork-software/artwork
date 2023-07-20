<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="user_to_edit">
        <UserTermsTab :user_to_edit="user_to_edit"></UserTermsTab>
    </UserEditHeader>
    <BaseSidenav :show="show" @toggle="this.show =! this.show">
        // Here User Sidenav
    </BaseSidenav>
</template>

<script>

import AppLayout from "@/Layouts/AppLayout.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import UserInfoTab from "@/Pages/Users/Tabs/UserInfoTab.vue";
import UserTermsTab from "@/Pages/Users/Tabs/UserTermsTab.vue";

export default {
    components: {
        UserTermsTab,
        UserEditHeader,
        BaseSidenav,
        AppLayout
    },
    props: [
        'user_to_edit',
        'currentTab',
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
