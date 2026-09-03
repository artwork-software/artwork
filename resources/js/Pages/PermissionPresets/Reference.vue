<template>
    <UserHeader
        title="Permissions explained"
        description="Every permission with what it unlocks, what it allows and what it needs to take effect."
    >
        <template #tabBar>
            <ToolbarHeader
                :icon="IconShieldLock"
                :title="$t('Permissions explained')"
                :description="$t('Reference of all permissions, grouped by module – for training and acceptance.')"
                icon-bg-class="bg-accent-50 text-accent-700"
            >
                <template #actions>
                    <BaseUIButton variant="secondary" :icon="IconPrinter" label="Print" @click="print" />
                </template>
            </ToolbarHeader>
        </template>

        <template #default>
            <div class="print:space-y-6">
                <PermissionCatalogEditor
                    :catalog="catalog"
                    :model-value="[]"
                    :presets="[]"
                    readonly
                />
            </div>
        </template>
    </UserHeader>
</template>

<script setup>
import { IconPrinter, IconShieldLock } from '@tabler/icons-vue'
import UserHeader from '@/Pages/Users/UserHeader.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PermissionCatalogEditor from '@/Artwork/Permissions/PermissionCatalogEditor.vue'

defineProps({
    catalog: { type: Object, required: true },
})

const print = () => window.print()
</script>
