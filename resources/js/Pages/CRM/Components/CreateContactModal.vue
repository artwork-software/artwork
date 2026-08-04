<template>
    <ArtworkBaseModal
        :title="$t('New contact')"
        :description="$t('Create a new CRM contact.')"
        modal-size="sm:max-w-3xl"
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
                                <IconChevronDown class="h-5 w-5 text-text-subtle" aria-hidden="true" />
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
                                                ? 'bg-accent-600 text-white'
                                                : isSelected
                                                    ? '!bg-accent-600/10'
                                                    : 'text-text',
                                            'relative cursor-default select-none py-2 pl-3 pr-9'
                                        ]"
                                    >
                                        <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                            {{ $t(type.name) }}
                                        </span>
                                        <span
                                            v-if="isSelected"
                                            :class="[active ? 'text-white' : 'text-accent-600', 'absolute inset-y-0 right-0 flex items-center pr-4']"
                                        >
                                            <IconCheck class="h-5 w-5" aria-hidden="true" />
                                        </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </div>
                </Listbox>
                <p v-if="form.errors.crm_contact_type_id" class="mt-1 text-xs text-danger">{{ form.errors.crm_contact_type_id }}</p>
            </div>

            <!-- Display Name -->
            <BaseInput
                id="display_name"
                v-model="form.display_name"
                :label="$t('Name')"
                :error="form.errors.display_name"
                required
            />

            <!-- Property groups (collapsible, from the creation mask) -->
            <div v-if="loadingMask" class="flex items-center gap-2 text-sm text-text-subtle py-2">
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ $t('Loading') }}...
            </div>

            <div v-else-if="maskGroups.length" class="space-y-2 max-h-[45vh] overflow-y-auto pr-1">
                <div v-for="group in maskGroups" :key="group.id" class="rounded-lg border border-border-subtle">
                    <button
                        type="button"
                        class="w-full px-4 py-3 flex items-center gap-3 text-left"
                        @click="toggleGroup(group.id)"
                    >
                        <PropertyIcon v-if="group.icon" :name="group.icon" class="h-4 w-4 text-text-subtle shrink-0" />
                        <span class="text-sm font-medium text-text">{{ $t(group.name) }}</span>
                        <span v-if="filledCount(group)" class="text-xs text-accent-600 tabular-nums">
                            {{ filledCount(group) }} {{ $t('filled') }}
                        </span>
                        <component
                            :is="IconChevronDown"
                            class="h-4 w-4 text-text-subtle ml-auto transition-transform shrink-0"
                            :class="expandedGroupIds.has(group.id) ? 'rotate-180' : ''"
                        />
                    </button>
                    <div v-if="expandedGroupIds.has(group.id)" class="border-t border-border-subtle px-4 py-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="property in group.properties" :key="property.id">
                                <CrmPropertyValueInput
                                    :property="property"
                                    :value="form.property_values[property.id] || ''"
                                    :required="!!property.is_required"
                                    uniform-label
                                    @update:value="(val) => form.property_values[property.id] = val"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" class="ui-button-cancel" @click="$emit('close')">
                    {{ $t('Cancel') }}
                </button>
                <BaseUIButton
                    type="button"
                    variant="primary"
                    hide-icon
                    @click="submit"
                    :disabled="form.processing"
                >
                    {{ $t('Create') }}
                </BaseUIButton>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
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

// -- Creation mask: all editable properties of the type, grouped --
const maskGroups = ref([])
const loadingMask = ref(false)
const expandedGroupIds = ref(new Set())

const loadMask = async () => {
    const slug = selectedType.value?.slug
    if (!slug) {
        maskGroups.value = []
        return
    }

    loadingMask.value = true
    try {
        const response = await axios.get(route('crm.contacts.mask'), { params: { type_slug: slug } })
        maskGroups.value = response.data.groups ?? []
        // Gruppen mit Pflichtfeldern starten aufgeklappt, der Rest eingeklappt
        expandedGroupIds.value = new Set(
            maskGroups.value
                .filter(g => (g.properties ?? []).some(p => p.is_required))
                .map(g => g.id)
        )
    } catch {
        maskGroups.value = []
    } finally {
        loadingMask.value = false
    }
}

const toggleGroup = (groupId) => {
    if (expandedGroupIds.value.has(groupId)) {
        expandedGroupIds.value.delete(groupId)
    } else {
        expandedGroupIds.value.add(groupId)
    }
}

const filledCount = (group) =>
    (group.properties ?? []).filter(p => {
        const v = form.property_values[p.id]
        return v !== undefined && v !== null && String(v).trim() !== ''
    }).length

onMounted(loadMask)

// Reset property values when type changes
watch(() => form.crm_contact_type_id, () => {
    form.property_values = {}
    loadMask()
})

const submit = () => {
    form.post(route('crm.contacts.store'), {
        onSuccess: () => emit('close'),
    })
}
</script>
