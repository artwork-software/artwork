<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="user_to_edit">
        <UserPermissionsTab
            :user_to_edit="user_to_edit"
            :catalog="catalog"
            :permission_presets="permission_presets"
            :permission_history="permission_history"
            :colleagues="colleagues"
            :available_roles="available_roles"
        />
    </UserEditHeader>
</template>

<script setup>
import UserEditHeader from '@/Pages/Users/Components/UserEditHeader.vue'
import UserPermissionsTab from '@/Pages/Users/Tabs/UserPermissionsTab.vue'

defineProps({
    user_to_edit: { type: Object, required: true },
    currentTab: { type: String, default: 'permissions' },
    catalog: { type: Object, required: true },
    permission_presets: { type: Array, default: () => [] },
    permission_history: { type: Array, default: () => [] },
    colleagues: { type: Array, default: () => [] },
    available_roles: { type: Array, default: () => [] },
})
</script>
