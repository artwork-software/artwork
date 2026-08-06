<template>
    <div class="flex items-center gap-x-4 border-b border-border-subtle bg-surface-sunken p-6">
        <img :src="route('generate-avatar-image', contact?.name?.[0] ?? 'A')" :alt="contact.name" class="size-12 flex-none rounded-full bg-surface object-cover border-2 border-white shadow-overlay" />
        <div class="w-full">
            <div class="font-lexend font-medium text-text">{{ contact.name }}</div>
            <div class="text-xs font-medium text-text">{{ contact.street }}</div>
            <div class="text-xs font-medium text-text" v-if="contact.zip_code && contact.location">{{ contact.zip_code }}, {{ contact.location }}</div>
        </div>
        <!-- Menu -->
        <BaseMenu has-no-offset white-menu-background>
            <BaseMenuItem @click="showCreateOrUpdateContactModal = true" title="Edit" :icon="IconEdit" white-menu-background/>
            <BaseMenuItem @click="showDeleteModal = true" title="Delete" :icon="IconTrash" white-menu-background/>
        </BaseMenu>
    </div>
    <dl class="-my-3 divide-y divide-border-subtle px-6 py-4 text-sm/6">
        <div class="flex justify-between gap-x-4 py-3">
            <dt class="text-text-subtle">{{ $t('Email') }}</dt>
            <dd class="text-text-muted">
                <div class="font-medium text-text">{{ contact.email ?? '-' }}</div>
            </dd>
        </div>
        <div class="flex justify-between gap-x-4 py-3">
            <dt class="text-text-subtle">{{ $t('Phone') }}</dt>
            <dd class="flex items-start gap-x-2">
                <div class="font-medium text-text">{{ contact.phone ?? '-' }}</div>
            </dd>
        </div>
        <div class="flex justify-between gap-x-4 py-3">
            <dt class="text-text-subtle">{{ $t('Mobile number') }}</dt>
            <dd class="flex items-start gap-x-2">
                <div class="font-medium text-text">{{ contact.mobile ?? '-' }}</div>
            </dd>
        </div>
        <div class="flex justify-between gap-x-4 py-3">
            <dt class="text-text-subtle">{{ $t('Fax number') }}</dt>
            <dd class="flex items-start gap-x-2">
                <div class="font-medium text-text">{{ contact.fax ?? '-' }}</div>
            </dd>
        </div>
    </dl>

    <GeneralCreateOrUpdateContactModal
        :contact="contact"
        title="Add accommodation contact"
        description="Add a new contact to the accommodation"
        v-if="showCreateOrUpdateContactModal"
        @close="showCreateOrUpdateContactModal = false"
    />

    <ArtworkBaseDeleteModal
        v-if="showDeleteModal"
        title="Delete contact"
        description="Are you sure you want to delete this contact?"
        @close="showDeleteModal = false"
        @delete="deleteContact"
    />
</template>

<script setup>

import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {defineAsyncComponent, ref} from "vue";
import {router} from "@inertiajs/vue3";
import {IconEdit, IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    contact: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            name: '',
            email: '',
            street: '',
            zip_code: '',
            location: '',
            phone: '',
            mobile: '',
            fax: '',
            is_primary: false,
        })
    }
})

const GeneralCreateOrUpdateContactModal = defineAsyncComponent({
    loader: () => import('@/Components/Modals/GeneralCreateOrUpdateContactModal.vue'),
    delay: 200,
    timeout: 3000,
})

const ArtworkBaseDeleteModal = defineAsyncComponent({
    loader: () => import('@/Artwork/Modals/ArtworkBaseDeleteModal.vue'),
    delay: 200,
    timeout: 3000,
})

const showCreateOrUpdateContactModal = ref(false)
const showDeleteModal = ref(false)

const deleteContact = () => {
    router.delete(route('contact.destroy', props.contact.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            showCreateOrUpdateContactModal.value = false;
        },
        onError: () => {
            console.log('Error deleting contact');
        }
    });
}
</script>

<style scoped>

</style>
