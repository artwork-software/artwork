<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="userToEdit">
        <UserWorkProfileTab :user="userToEdit"/>
    </UserEditHeader>
</template>

<script>
import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import UserWorkProfileTab from "@/Pages/Users/Tabs/UserWorkProfileTab.vue";

export default {
    components: {
        UserWorkProfileTab,
        UserEditHeader
    },
    props: ['userToEdit', 'currentTab']
}
</script>
