<template>
    <ExternalModal
        :title="isEdit ? $t('Edit {type}', { type: contactType.name }) : $t('Add {type}', { type: contactType.name })"
        :description="$t('Your entries are visible to the inviting institution.')"
        @close="$emit('close')"
    >
        <p v-if="loading" class="text-sm text-text-subtle">{{ $t('Loading data...') }}</p>
        <p v-else-if="loadError" class="text-sm text-danger">{{ loadError }}</p>
        <form v-else id="external-crm-contact-form" class="space-y-6" @submit.prevent="save">
            <div>
                <label for="crm-contact-display-name" class="block text-sm font-medium text-text-muted">
                    {{ $t('Name') }}<span class="ml-0.5 text-danger">*</span>
                </label>
                <input
                    id="crm-contact-display-name"
                    v-model="displayName"
                    type="text"
                    maxlength="255"
                    class="mt-1 block w-full rounded-lg border border-border px-3 py-2 text-sm focus:border-accent-600 focus:outline-none focus:ring-1 focus:ring-accent-600"
                />
                <p v-if="errors.display_name" class="mt-1 text-xs text-danger">{{ errors.display_name }}</p>
            </div>

            <section v-for="group in groups" :key="group.id ?? group.name">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-text-subtle">{{ group.name }}</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <ExternalCrmPropertyField
                        v-for="property in group.properties"
                        :key="property.id"
                        v-model="values[property.id]"
                        :property="property"
                        :class="property.type === 'textarea' ? 'sm:col-span-2' : ''"
                        :error="errors[`property_values.${property.id}`] ?? ''"
                    />
                </div>
            </section>

            <p v-if="errors.general" class="text-sm text-danger">{{ errors.general }}</p>
        </form>

        <template #footer>
            <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-text ring-1 ring-inset ring-border" @click="$emit('close')">
                {{ $t('Cancel') }}
            </button>
            <button
                type="submit"
                form="external-crm-contact-form"
                :disabled="saving || loading || !!loadError"
                class="rounded-lg bg-surface-inverse px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:bg-border-strong"
            >
                {{ saving ? $t('Saving...') : $t('Save') }}
            </button>
        </template>
    </ExternalModal>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { useTranslation } from '@/Composeables/Translation.js'
import ExternalModal from '../ExternalModal.vue'
import ExternalCrmPropertyField from './ExternalCrmPropertyField.vue'

const props = defineProps({
    routeParams: { type: Object, required: true },
    contactType: { type: Object, required: true },
    // bestehender Kontakt (Bearbeiten) oder null (Anlegen)
    contact: { type: Object, default: null },
})
const emit = defineEmits(['close', 'saved'])

const $t = useTranslation()
const isEdit = computed(() => props.contact !== null)

const loading = ref(true)
const loadError = ref('')
const saving = ref(false)
const groups = ref([])
const displayName = ref(props.contact?.display_name ?? '')
const values = ref({ ...(props.contact?.values ?? {}) })
const errors = ref({})

onMounted(async () => {
    try {
        const { data } = await axios.get(route('external.project.tab.crm-contacts.mask', props.routeParams), {
            params: { contact_type_id: props.contactType.id },
        })
        groups.value = data.groups ?? []
    } catch (e) {
        loadError.value = e?.response?.data?.message ?? $t('Could not load data.')
    } finally {
        loading.value = false
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
            ? await axios.patch(
                route('external.project.tab.crm-contacts.update', { ...props.routeParams, crmContact: props.contact.id }),
                payload,
            )
            : await axios.post(route('external.project.tab.crm-contacts.store', props.routeParams), payload)
        emit('saved', data.contact)
        emit('close')
    } catch (e) {
        const serverErrors = e?.response?.data?.errors
        if (serverErrors) {
            errors.value = Object.fromEntries(
                Object.entries(serverErrors).map(([key, messages]) => [key, Array.isArray(messages) ? messages[0] : messages]),
            )
            if (serverErrors.crm_contact_type_id) {
                errors.value.general = errors.value.crm_contact_type_id
            }
        } else {
            errors.value = { general: e?.response?.data?.message ?? $t('Could not save. Try again.') }
        }
    } finally {
        saving.value = false
    }
}
</script>
