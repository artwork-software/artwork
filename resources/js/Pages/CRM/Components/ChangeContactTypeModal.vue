<template>
    <ArtworkBaseModal
        :title="$t('Change contact type')"
        :description="$t('Move this contact to a different contact type.')"
        @close="$emit('close')"
    >
        <div class="mt-4 space-y-4">
            <ArtworkBaseListbox
                v-model="selectedType"
                :items="selectableTypes"
                by="id"
                option-label="name"
                option-key="id"
                :label="$t('New contact type')"
                :placeholder="$t('Select contact type')"
                :use-translations="true"
            />

            <div v-if="selectedType && lostValues.length" class="rounded-md bg-red-50 border border-red-200 p-4">
                <p class="text-sm font-medium text-red-800">
                    {{ $t('The following values are not part of the new contact type and will be permanently deleted:') }}
                </p>
                <ul class="mt-2 space-y-0.5">
                    <li v-for="entry in lostValues" :key="entry.id" class="text-sm text-red-700">
                        <span class="font-medium">{{ entry.name }}:</span> {{ entry.value }}
                    </li>
                </ul>
            </div>
            <div v-else-if="selectedType" class="rounded-md bg-green-50 border border-green-200 p-4">
                <p class="text-sm text-green-800">{{ $t('All current values are also available in the new contact type.') }}</p>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button class="ui-button-cancel" @click="$emit('close')">{{ $t('Cancel') }}</button>
                <button class="ui-button-add" :disabled="!selectedType || processing" @click="submit">
                    {{ $t('Change type') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()

const props = defineProps({
    contact: { type: Object, required: true },
    contactTypes: { type: Array, required: true },
})

const emit = defineEmits(['close'])

const mirroredSlugs = ['user', 'freelancer', 'service_provider']

const selectableTypes = computed(() =>
    props.contactTypes.filter(t =>
        t.id !== props.contact.crm_contact_type_id && !mirroredSlugs.includes(t.slug)
    )
)

const selectedType = ref(null)
const processing = ref(false)

// Werte, deren Eigenschaft dem neuen Typ nicht zugewiesen ist — sie werden beim
// Wechsel serverseitig gelöscht.
const lostValues = computed(() => {
    if (!selectedType.value) return []
    const keptIds = new Set((selectedType.value.properties ?? []).map(p => p.id))

    return (props.contact.property_values ?? [])
        .filter(v => v.value !== null && String(v.value).trim() !== '' && !keptIds.has(v.crm_property_id))
        .map(v => ({
            id: v.id,
            name: v.property?.name ?? `#${v.crm_property_id}`,
            value: v.value,
        }))
})

const submit = () => {
    processing.value = true
    router.patch(route('crm.contacts.change-type', props.contact.id), {
        crm_contact_type_id: selectedType.value.id,
    }, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
        onFinish: () => { processing.value = false },
    })
}
</script>
