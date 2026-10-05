<template>
    <ArtworkBaseModal
        :title="isEdit ? $t('Edit {type}', { type: contactType.name }) : $t('Add {type}', { type: contactType.name })"
        :description="isEdit ? $t('Changes are saved directly to the CRM contact.') : $t('The contact is created in the CRM and linked to this project.')"
        modal-size="max-w-2xl"
        @close="$emit('close')"
    >
        <div class="mt-4">
            <div v-if="maskLoading" class="py-4 text-xs text-text-subtle">{{ $t('Loading data...') }}</div>
            <div v-else-if="maskError" class="py-4 text-xs text-danger">{{ $t('Failed to load data') }}</div>
            <div v-else class="space-y-5">
                <BaseInput
                    id="crm-contact-list-display-name"
                    v-model="displayName"
                    :label="$t('Name')"
                    without-translation
                    :error="errors.display_name"
                    required
                />

                <div v-for="group in groups" :key="group.id ?? group.name">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-subtle">{{ $t(group.name) }}</div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <CrmPropertyValueInput
                            v-for="property in group.properties"
                            :key="property.id"
                            :property="property"
                            :value="values[property.id] ?? ''"
                            :required="property.is_required"
                            :error="errors[`property_values.${property.id}`] ?? ''"
                            :class="property.type === 'textarea' ? 'sm:col-span-2' : ''"
                            @update:value="(value) => (values[property.id] = value)"
                        />
                    </div>
                </div>

                <p v-if="errors.general" class="text-xs text-danger">{{ errors.general }}</p>

                <div class="flex justify-end gap-3 pt-2">
                    <BaseUIButton type="button" variant="secondary" hide-icon @click="$emit('close')">
                        {{ $t('Cancel') }}
                    </BaseUIButton>
                    <BaseUIButton type="button" variant="primary" hide-icon :disabled="saving" @click="save">
                        {{ isEdit ? $t('Save') : $t('Create and link') }}
                    </BaseUIButton>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import CrmPropertyValueInput from '@/Pages/CRM/Components/CrmPropertyValueInput.vue'
import { useTranslation } from '@/Composeables/Translation.js'

defineOptions({ name: 'CrmContactFormModal' })

const props = defineProps({
    projectId: { type: [Number, String], required: true },
    componentId: { type: [Number, String], required: true },
    contactType: { type: Object, required: true },
    contact: { type: Object, default: null },
})
const emit = defineEmits(['close', 'saved'])

const $t = useTranslation()
const isEdit = computed(() => props.contact !== null)
const routeParams = computed(() => ({ project: props.projectId, component: props.componentId }))

const maskLoading = ref(true)
const maskError = ref(false)
const groups = ref([])
const displayName = ref(props.contact?.display_name ?? '')
const values = ref({ ...(props.contact?.values ?? {}) })
const errors = ref({})
const saving = ref(false)

onMounted(async () => {
    try {
        const { data } = await axios.get(route('projects.components.crm-contacts.mask', routeParams.value), {
            params: { contact_type_id: props.contactType.id },
        })
        groups.value = data.groups ?? []
    } catch (e) {
        maskError.value = true
    } finally {
        maskLoading.value = false
    }
})

function validate() {
    const result = {}
    if (!displayName.value.trim()) {
        result.display_name = $t('This is a mandatory field.')
    }
    for (const group of groups.value) {
        for (const property of group.properties) {
            const value = values.value[property.id]
            if (property.is_required && (value === undefined || value === null || String(value).trim() === '')) {
                result[`property_values.${property.id}`] = $t('This is a mandatory field.')
            }
        }
    }
    return result
}

async function save() {
    errors.value = validate()
    if (Object.keys(errors.value).length) return

    saving.value = true
    const payload = {
        crm_contact_type_id: props.contactType.id,
        display_name: displayName.value.trim(),
        property_values: values.value,
    }
    try {
        const { data } = isEdit.value
            ? await axios.patch(route('projects.components.crm-contacts.update', { ...routeParams.value, crmContact: props.contact.id }), payload)
            : await axios.post(route('projects.components.crm-contacts.store', routeParams.value), payload)
        emit('saved', data.contact)
        emit('close')
    } catch (e) {
        const serverErrors = e?.response?.data?.errors
        errors.value = serverErrors
            ? Object.fromEntries(Object.entries(serverErrors).map(([key, messages]) => [key, Array.isArray(messages) ? messages[0] : messages]))
            : { general: e?.response?.data?.message ?? $t('Failed to save') }
        if (serverErrors?.crm_contact_type_id) {
            errors.value.general = errors.value.crm_contact_type_id
        }
    } finally {
        saving.value = false
    }
}
</script>
