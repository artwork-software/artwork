<template>
    <ArtworkBaseModal
        @close="$emit('close')"
        v-if="true"
        :title="$t('Search CRM contact')"
        :description="$t('Search for a CRM contact to link as contract partner.')"
    >
        <div class="space-y-4">
            <!-- Contact Type Filter -->
            <div>
                <ArtworkBaseListbox
                    :model-value="selectedType"
                    @update:model-value="onTypeChange"
                    :items="typeOptions"
                    by="id"
                    option-label="name"
                    :label="$t('Contact type')"
                    :placeholder="$t('All contact types')"
                    :enable-search="false"
                />
            </div>

            <!-- Search Input -->
            <BaseInput
                v-model="searchQuery"
                id="crmContactSearch"
                :label="$t('Search by name')"
            />

            <!-- Results -->
            <div class="max-h-64 overflow-y-auto">
                <div v-if="loading" class="py-4 text-center text-sm text-gray-500">
                    {{ $t('Searching...') }}
                </div>

                <div v-else-if="results.length === 0 && hasSearched" class="py-4 text-center text-sm text-gray-500">
                    {{ $t('No contacts found.') }}
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <button
                        v-for="contact in results"
                        :key="contact.id"
                        @click="selectContact(contact)"
                        class="w-full flex items-center gap-3 px-3 py-3 hover:bg-gray-50 transition-colors text-left"
                    >
                        <img
                            v-if="contact.profile_photo_url"
                            :src="contact.profile_photo_url"
                            alt=""
                            class="h-10 w-10 rounded-full object-cover flex-shrink-0"
                        />
                        <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                            <component :is="IconUser" class="h-5 w-5 text-gray-500" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 truncate">{{ contact.display_name }}</div>
                            <div v-if="contact.contact_type" class="mt-0.5">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :style="contact.contact_type.color ? { backgroundColor: contact.contact_type.color + '20', color: contact.contact_type.color } : {}"
                                    :class="!contact.contact_type.color ? 'bg-gray-100 text-gray-700' : ''"
                                >
                                    {{ contact.contact_type.name }}
                                </span>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { IconUser } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'

const props = defineProps({
    contactTypes: {
        type: Array,
        default: () => []
    },
})

const emit = defineEmits(['close', 'contact-selected'])

const searchQuery = ref('')
const selectedTypeId = ref(null)

const typeOptions = computed(() => props.contactTypes)
const selectedType = computed(() =>
    typeOptions.value.find(t => t.id === selectedTypeId.value) ?? null
)
const onTypeChange = (type) => {
    selectedTypeId.value = type?.id ?? null
}
const results = ref([])
const loading = ref(false)
const hasSearched = ref(false)

let debounceTimer = null

const performSearch = () => {
    loading.value = true
    hasSearched.value = true

    axios.get(route('crm.contacts.search'), {
        params: {
            search: searchQuery.value || undefined,
            type_id: selectedTypeId.value || undefined,
        }
    }).then(response => {
        results.value = response.data
    }).catch(() => {
        results.value = []
    }).finally(() => {
        loading.value = false
    })
}

watch([searchQuery, selectedTypeId], () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(performSearch, 300)
}, { immediate: false })

// Initial load
performSearch()

const selectContact = (contact) => {
    emit('contact-selected', contact)
    emit('close')
}
</script>
