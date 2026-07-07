<template>
    <ArtworkBaseModal
        :title="$t('New contact')"
        :description="$t('Create a new CRM contact.')"
        @close="$emit('close')"
    >
        <div class="space-y-4 mt-4">
            <!-- Contact Type -->
            <div>
                <Listbox as="div" v-model="form.crm_contact_type_id">
                    <ListboxLabel class="componentLabel">{{ $t('Contact type') }}</ListboxLabel>
                    <div class="relative mt-2">
                        <ListboxButton class="menu-button bg-white">
                            <div class="block truncate">{{ selectedTypeLabel }}</div>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true" />
                            </span>
                        </ListboxButton>

                        <transition
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <ListboxOptions
                                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm"
                            >
                                <ListboxOption
                                    as="template"
                                    v-for="type in creatableTypes"
                                    :key="type.id"
                                    :value="type.id"
                                    v-slot="{ active, selected: isSelected }"
                                >
                                    <li
                                        :class="[
                                            active
                                                ? 'bg-indigo-600 text-white'
                                                : isSelected
                                                    ? '!bg-artwork-action-buttons/10'
                                                    : 'text-gray-900',
                                            'relative cursor-default select-none py-2 pl-3 pr-9'
                                        ]"
                                    >
                                        <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                            {{ $t(type.name) }}
                                        </span>
                                        <span
                                            v-if="isSelected"
                                            :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']"
                                        >
                                            <IconCheck class="h-5 w-5" aria-hidden="true" />
                                        </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </div>
                </Listbox>
                <p v-if="form.errors.crm_contact_type_id" class="mt-1 text-xs text-artwork-messages-error">{{ form.errors.crm_contact_type_id }}</p>
            </div>

            <!-- Display Name -->
            <BaseInput
                id="display_name"
                v-model="form.display_name"
                :label="$t('Name')"
                :error="form.errors.display_name"
                required
            />

            <!-- Required Properties -->
            <template v-if="requiredProperties.length">
                <div v-for="property in requiredProperties" :key="property.id">
                    <CrmPropertyValueInput
                        :property="property"
                        :value="form.property_values[property.id] || ''"
                        @update:value="(val) => form.property_values[property.id] = val"
                    />
                </div>
            </template>

            <!-- Actions -->
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" class="ui-button-cancel" @click="$emit('close')">
                    {{ $t('Cancel') }}
                </button>
                <button
                    type="button"
                    class="ui-button-add"
                    @click="submit"
                    :disabled="form.processing"
                >
                    {{ $t('Create') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import CrmPropertyValueInput from '@/Pages/CRM/Components/CrmPropertyValueInput.vue'
import { IconChevronDown, IconCheck } from '@tabler/icons-vue'

const mirroredSlugs = ['user', 'freelancer', 'service_provider']

const props = defineProps({
    contactTypes: { type: Array, required: true },
    activeType: { type: Object, default: null },
})

const emit = defineEmits(['close'])

const creatableTypes = computed(() => {
    return props.contactTypes.filter(t => !mirroredSlugs.includes(t.slug))
})

const defaultTypeId = computed(() => {
    if (props.activeType && !mirroredSlugs.includes(props.activeType.slug)) {
        return props.activeType.id
    }
    return creatableTypes.value[0]?.id
})

const form = useForm({
    crm_contact_type_id: defaultTypeId.value,
    display_name: '',
    property_values: {},
})

const selectedType = computed(() => {
    return creatableTypes.value.find(t => t.id === form.crm_contact_type_id)
})

const selectedTypeLabel = computed(() => {
    return selectedType.value?.name ?? ''
})

const requiredProperties = computed(() => {
    return (selectedType.value?.properties ?? []).filter(p => p.pivot?.is_required)
})

// Reset property values when type changes
watch(() => form.crm_contact_type_id, () => {
    form.property_values = {}
})

const submit = () => {
    form.post(route('crm.contacts.store'), {
        onSuccess: () => emit('close'),
    })
}
</script>
