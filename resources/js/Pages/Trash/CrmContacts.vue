<template>
    <TrashTab
        :paginator="trashed_contacts"
        property-name="trashed_contacts"
        :force-all-href="route('crm.contacts.force.all')"
        :restore-href="(contact) => route('crm.contacts.restore', { id: contact.id })"
        :force-href="(contact) => route('crm.contacts.force', { id: contact.id })"
        :name-of="(contact) => contact.display_name"
        :open-href="(contact) => route('crm.contacts.show', { crmContact: contact.id })"
    >
        <template #leading="{ item: contact }">
            <img :src="contact.profile_photo_url" alt="" class="size-10 shrink-0 rounded-full object-cover" />
        </template>
        <template #default="{ item: contact }">
            <div class="truncate font-medium text-text">{{ contact.display_name }}</div>
            <div v-if="contact.contact_type" class="mt-0.5">
                <span
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :style="contact.contact_type.color
                        ? { backgroundColor: contact.contact_type.color + '15', color: contact.contact_type.color }
                        : { backgroundColor: '#eef2ff', color: '#3730a3' }"
                >
                    {{ $t(contact.contact_type.name) }}
                </span>
            </div>
        </template>
    </TrashTab>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import TrashLayout from '@/Layouts/TrashLayout.vue'

export default {
    layout: [AppLayout, TrashLayout],
}
</script>

<script setup>
import TrashTab from '@/Pages/Trash/Components/TrashTab.vue'
defineProps({
    trashed_contacts: { type: Object, required: true },
})
</script>
