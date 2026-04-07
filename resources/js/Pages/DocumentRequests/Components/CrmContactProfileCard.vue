<template>
    <div v-if="contactData" class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <img
                    v-if="contactData.contact.profile_photo_url"
                    :src="contactData.contact.profile_photo_url"
                    alt=""
                    class="h-10 w-10 rounded-full object-cover"
                />
                <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                    <component :is="IconUser" class="h-5 w-5 text-gray-500" />
                </div>
                <div>
                    <div class="text-sm font-semibold text-gray-900">{{ contactData.contact.display_name }}</div>
                    <span
                        v-if="contactData.contact.contact_type"
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700"
                    >
                        {{ contactData.contact.contact_type.name }}
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a :href="route('crm.contacts.show', contactData.contact.id)" class="text-xs text-blue-600 hover:text-blue-800 hover:underline">
                    {{ $t('View in CRM') }}
                </a>
                <button
                    v-if="!readonly"
                    @click="$emit('unlink')"
                    class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                    :title="$t('Unlink CRM contact')"
                >
                    <component :is="IconX" class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Property Groups -->
        <div v-if="visibleGroups.length > 0" class="p-4 space-y-3">
            <CrmPropertyGroupSection
                v-for="group in visibleGroups"
                :key="group.id"
                :group="group"
                :contact="contactData.contact"
                :editing="false"
            />
        </div>
    </div>

    <div v-else-if="loadingData" class="py-4 text-center text-sm text-gray-500">
        {{ $t('Loading...') }}
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { IconUser, IconX } from '@tabler/icons-vue'
import CrmPropertyGroupSection from '@/Pages/CRM/Components/CrmPropertyGroupSection.vue'

const props = defineProps({
    documentRequestId: {
        type: Number,
        required: true,
    },
    crmContact: {
        type: Object,
        default: null,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['unlink'])

const contactData = ref(null)
const loadingData = ref(true)

const contactTypePivotMap = computed(() => {
    if (!contactData.value?.contact?.contact_type?.properties) return {}
    const map = {}
    for (const p of contactData.value.contact.contact_type.properties) {
        map[p.id] = p.pivot ?? {}
    }
    return map
})

const visibleGroups = computed(() => {
    if (!contactData.value) return []

    const typePropertyIds = new Set(
        (contactData.value.contact.contact_type?.properties ?? []).map(p => p.id)
    )

    return (contactData.value.propertyGroups ?? [])
        .map(group => ({
            ...group,
            properties: (group.properties ?? [])
                .filter(p => typePropertyIds.has(p.id))
                .map(p => ({ ...p, pivot: contactTypePivotMap.value[p.id] ?? {} })),
        }))
        .filter(group => group.properties.length > 0)
})

onMounted(() => {
    axios.get(route('document-requests.crm-contact', props.documentRequestId))
        .then(response => {
            contactData.value = response.data
        })
        .catch(() => {
            contactData.value = null
        })
        .finally(() => {
            loadingData.value = false
        })
})
</script>
