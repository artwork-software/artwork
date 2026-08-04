<template>
    <ArtworkBaseModal
        :title="$t('New CRM artist')"
        :description="$t('Create a new CRM contact.')"
        modal-size="max-w-2xl"
        @close="$emit('close')"
    >
        <div class="mt-4">
            <div v-if="maskLoading" class="text-xs text-text-subtle py-4">
                {{ $t('Loading data...') }}
            </div>
            <div v-else-if="maskError" class="text-xs text-danger py-4">
                {{ $t('Failed to load data') }}
            </div>
            <div v-else class="space-y-5">
                <BaseInput
                    id="new-crm-artist-display-name"
                    v-model="displayName"
                    :label="$t('Name')"
                    without-translation
                    :error="errors.display_name"
                    required
                />

                <div v-for="group in groups" :key="group.id">
                    <div class="text-xs font-semibold text-text-subtle uppercase tracking-wide mb-2">
                        {{ $t(group.name) }}
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="property in group.properties" :key="property.id">
                            <CrmPropertyValueInput
                                :property="property"
                                :value="propertyValues[property.id] || ''"
                                :required="property.is_required"
                                :error="errors[property.id] || ''"
                                @update:value="(val) => onUpdateValue(property.id, val)"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="ui-button-cancel" @click="$emit('close')">
                        {{ $t('Cancel') }}
                    </button>
                    <BaseUIButton
                        type="button"
                        variant="primary"
                        hide-icon
                        :disabled="submitting"
                        @click="submit"
                    >
                        {{ $t('Create and link') }}
                    </BaseUIButton>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue';
import axios from 'axios';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import CrmPropertyValueInput from '@/Pages/CRM/Components/CrmPropertyValueInput.vue';

const emit = defineEmits(['close', 'created']);

const { proxy } = getCurrentInstance();
const $t = proxy.$t;

const maskLoading = ref(true);
const maskError = ref(false);
const contactType = ref(null);
const groups = ref([]);

const displayName = ref('');
const propertyValues = ref({});
const errors = ref({});
const submitting = ref(false);

onMounted(async () => {
    try {
        const response = await axios.get(route('crm.contacts.mask'), {
            params: { type_slug: 'artist' },
        });
        contactType.value = response.data.contact_type;
        groups.value = response.data.groups ?? [];
    } catch (e) {
        maskError.value = true;
    } finally {
        maskLoading.value = false;
    }
});

function onUpdateValue(propertyId, value) {
    propertyValues.value = { ...propertyValues.value, [propertyId]: value };
    if (errors.value[propertyId]) {
        const next = { ...errors.value };
        delete next[propertyId];
        errors.value = next;
    }
}

function validate() {
    const result = {};
    if (!displayName.value.trim()) {
        result.display_name = $t('This is a mandatory field.');
    }
    for (const group of groups.value) {
        for (const property of group.properties) {
            if (!property.is_required) continue;
            const value = propertyValues.value[property.id];
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                result[property.id] = $t('This is a mandatory field.');
            }
        }
    }
    return result;
}

async function submit() {
    const validationErrors = validate();
    if (Object.keys(validationErrors).length > 0) {
        errors.value = validationErrors;
        return;
    }
    errors.value = {};
    submitting.value = true;

    try {
        const response = await axios.post(route('crm.contacts.store'), {
            crm_contact_type_id: contactType.value.id,
            display_name: displayName.value.trim(),
            property_values: propertyValues.value,
        });
        emit('created', response.data);
        emit('close');
    } catch (e) {
        if (e.response?.status === 422 && e.response.data?.errors) {
            const serverErrors = {};
            for (const [key, messages] of Object.entries(e.response.data.errors)) {
                serverErrors[key] = Array.isArray(messages) ? messages[0] : messages;
            }
            errors.value = serverErrors;
        } else {
            errors.value = { display_name: $t('Failed to load data') };
        }
    } finally {
        submitting.value = false;
    }
}
</script>
