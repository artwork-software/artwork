<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="userToEdit">
        <WorkProfileTab user-type="user" :user="userToEdit"/>
    </UserEditHeader>
</template>

<script>
import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import WorkProfileTab from "@/Pages/Components/WorkProfileTab.vue";

export default {
    components: {
        WorkProfileTab,
        UserEditHeader
    },
    props: ['userToEdit', 'currentTab']
}
</script>
