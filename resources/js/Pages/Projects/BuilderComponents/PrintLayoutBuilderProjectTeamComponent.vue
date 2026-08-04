<template>
    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-secondary mb-2">{{ $t('Project team') }}</h3>
        <div v-if="project.team?.length > 0 || project.teamCrmContacts?.length > 0" class="space-y-1">
            <div v-for="(user, index) in project.team" :key="index" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-border-subtle">
                <div class="flex items-center">
                    <div>
                        <img class="inline-block size-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                    </div>
                    <div class="mx-2">
                        <p class="xsDark group-hover:text-text">{{ user.full_name }}</p>
                    </div>
                </div>
            </div>
            <!-- CRM-Kontakte im Team: Badge am Avatar + Typ-Label kennzeichnen die Herkunft aus dem CRM -->
            <div v-for="contact in (project.teamCrmContacts ?? [])" :key="`crm-${contact.id}`" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-border-subtle">
                <div class="flex items-center">
                    <span class="relative inline-flex shrink-0">
                        <img class="inline-block size-9 rounded-full object-cover ring-2" :style="{ '--tw-ring-color': contact.contact_type?.color || '#6b7280' }" :src="contact.profile_photo_url" alt="" />
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full ring-2 ring-white" :style="{ backgroundColor: contact.contact_type?.color || '#6b7280' }">
                            <PropertyIcon :name="contact.contact_type?.icon || 'IconAddressBook'" class="h-2 w-2 text-white" stroke-width="2.5"/>
                        </span>
                    </span>
                    <div class="mx-2 flex items-center gap-x-1.5">
                        <p class="xsDark group-hover:text-text">{{ contact.display_name }}</p>
                        <span v-if="contact.contact_type" class="text-[10px] text-secondary whitespace-nowrap">
                            ({{ $t(contact.contact_type.name) }})
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-sm text-secondary">
            {{ $t('No entries') }}
        </div>
    </div>
</template>
<script setup>
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: false,
    },
})
</script>
<style scoped>
</style>
