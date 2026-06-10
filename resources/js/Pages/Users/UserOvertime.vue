<template>
    <UserEditHeader :user_to_edit="userToEdit">
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-semibold mb-1">{{ $t('Overtime') }}</h2>
                <p class="text-sm text-gray-600">
                    {{ $t('Overtime account, deadlines and payouts for this user.') }}
                </p>
            </div>

            <UserOvertimePanel :user-id="userToEdit.id" :data="overtime" />
        </div>
    </UserEditHeader>
</template>

<script setup>
import UserEditHeader from '@/Pages/Users/Components/UserEditHeader.vue';
import UserOvertimePanel from '@/Pages/Shifts/Components/UserOvertimePanel.vue';

defineProps({
    userToEdit: { type: Object, required: true },
    overtime: { type: Object, required: true },
    currentTab: { type: String, default: 'overtime' },
});
</script>
