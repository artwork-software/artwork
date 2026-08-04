<template>
    <AppLayout :title="$t('Import contacts')">
        <div class="mt-5 mx-auto container pb-20">
            <ToolbarHeader
                :icon="IconUpload"
                :title="$t('Import contacts')"
                icon-bg-class="bg-accent-50 text-accent-700"
                :search-enabled="false"
            >
                <template #actions>
                    <Link :href="route('crm.index')" class="ui-button">
                        {{ $t('Back to CRM') }}
                    </Link>
                </template>
            </ToolbarHeader>

            <ImportStepper
                :steps="form.use_type_column ? ['Upload file', 'Map type values', 'Map columns'] : ['Upload file', 'Map columns']"
                :current-step="1"
            />

            <!-- Error -->
            <div v-if="error" class="mb-6 rounded-md bg-danger-surface p-4">
                <p class="text-sm font-medium text-danger">{{ error }}</p>
            </div>

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_380px]">
                <form @submit.prevent="submit" class="max-w-xl space-y-6">
                    <!-- Use Type Column Toggle -->
                    <ArtworkBaseToggle
                        v-model="form.use_type_column"
                        :label="$t('Determine different contact types by column in Excel')"
                        :description="$t('If active, you select a column in the next step from which values are read and then you can assign the values to the contact types in the CRM. If inactive, all imported entries are assigned to the same selected contact type.')"
                    />

                    <!-- Contact Type (only when not using type column) -->
                    <div v-if="!form.use_type_column">
                        <Listbox as="div" v-model="form.crm_contact_type_id">
                            <ListboxLabel class="componentLabel">{{ $t('Contact type') }}</ListboxLabel>
                            <div class="relative mt-2">
                                <ListboxButton class="menu-button bg-white">
                                    <div class="flex items-center gap-2 truncate">
                                        <PropertyIcon v-if="selectedType?.icon" :name="selectedType.icon" class="size-5 flex-shrink-0" :style="selectedType.color ? { color: selectedType.color } : {}" />
                                        <span
                                            v-if="selectedType?.color"
                                            class="inline-block size-2.5 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: selectedType.color }"
                                        ></span>
                                        <span>{{ selectedType ? $t(selectedType.name) : $t('Select contact type') }}</span>
                                    </div>
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
                                            v-for="type in contactTypes"
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
                                                <div class="flex items-center gap-2">
                                                    <PropertyIcon v-if="type.icon" :name="type.icon" class="size-5 flex-shrink-0" :style="type.color ? { color: active ? 'white' : type.color } : {}" />
                                                    <span
                                                        v-if="type.color"
                                                        class="inline-block size-2.5 rounded-full flex-shrink-0"
                                                        :style="{ backgroundColor: active ? 'white' : type.color }"
                                                    ></span>
                                                    <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                                        {{ $t(type.name) }}
                                                    </span>
                                                </div>
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
                        <p v-if="form.errors.crm_contact_type_id" class="mt-1 text-sm text-danger">
                            {{ form.errors.crm_contact_type_id }}
                        </p>
                    </div>

                    <!-- Missing contact type hint -->
                    <div class="rounded-lg border border-border-subtle bg-surface-sunken p-4">
                        <p class="text-sm text-text-muted">
                            {{ $t('Contact type missing or incomplete? You can create a new contact type right here — including its property groups.') }}
                        </p>
                        <div class="mt-2 flex items-center gap-4">
                            <button type="button" class="text-sm font-medium text-accent-600 hover:text-accent-600 flex items-center gap-1" @click="showTypeModal = true">
                                <component :is="IconCirclePlus" class="h-4 w-4" />
                                {{ $t('New contact type') }}
                            </button>
                            <Link :href="route('crm.settings.index')" class="text-sm font-medium text-accent-600 hover:text-accent-600 flex items-center gap-1">
                                <component :is="IconSettings" class="h-4 w-4" />
                                {{ $t('Open CRM settings') }}
                            </Link>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-text-muted">
                            {{ $t('Upload file') }}
                        </label>
                        <div
                            class="mt-1 flex justify-center rounded-lg border-2 border-dashed px-6 py-10 transition-colors"
                            :class="isDragging ? 'border-accent-600 bg-accent-50' : 'border-border'"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="onDrop"
                        >
                            <div class="text-center">
                                <component :is="IconFileSpreadsheet" class="mx-auto h-12 w-12 text-text-subtle" />
                                <div class="mt-4 flex text-sm leading-6 text-text-muted">
                                    <label
                                        for="file-upload"
                                        class="relative cursor-pointer rounded-md font-semibold text-accent-600 hover:text-accent-600"
                                    >
                                        <span>{{ $t('Choose file') }}</span>
                                        <input
                                            id="file-upload"
                                            type="file"
                                            class="sr-only"
                                            accept=".csv,.xlsx,.xls"
                                            @change="onFileChange"
                                        />
                                    </label>
                                    <p class="pl-1">{{ $t('or drag and drop') }}</p>
                                </div>
                                <p class="text-xs leading-5 text-text-subtle">
                                    {{ $t('Supported formats: CSV, XLSX, XLS') }} (max. 10 MB)
                                </p>
                                <p v-if="form.file" class="mt-3 text-sm font-medium text-accent-600">
                                    {{ form.file.name }}
                                </p>
                            </div>
                        </div>
                        <p v-if="form.errors.file" class="mt-1 text-sm text-danger">
                            {{ form.errors.file }}
                        </p>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end">
                        <BaseUIButton
                            type="submit"
                            variant="primary"
                            hide-icon
                            :disabled="!canSubmit || form.processing"
                            class="disabled:bg-surface-canvas disabled:border-border-subtle disabled:text-text-subtle disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                {{ $t('Uploading') }}...
                            </span>
                            <span v-else>{{ form.use_type_column ? $t('Next: Map type values') : $t('Next: Map columns') }}</span>
                        </BaseUIButton>
                    </div>
                </form>

                <!-- How the import works -->
                <aside class="rounded-2xl border border-accent-200 bg-accent-50 p-5 h-fit">
                    <div class="flex items-center gap-2 mb-3">
                        <component :is="IconInfoCircle" class="h-5 w-5 text-accent-600" />
                        <h3 class="text-sm font-semibold text-text">{{ $t('How the import works') }}</h3>
                    </div>
                    <ol class="space-y-3">
                        <li v-for="(hint, index) in importHints" :key="index" class="flex gap-2.5">
                            <span class="flex items-center justify-center size-5 rounded-full bg-accent-600 text-white text-[11px] font-bold shrink-0 mt-0.5">{{ index + 1 }}</span>
                            <p class="text-xs text-text-muted leading-5">{{ $t(hint) }}</p>
                        </li>
                    </ol>
                </aside>
            </div>
        </div>

        <!-- Create contact type incl. property group assignment -->
        <AddEditTypeModal
            v-if="showTypeModal"
            :contact-type="null"
            :property-groups="propertyGroups"
            @close="showTypeModal = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { useTranslation } from '@/Composeables/Translation.js'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
import ImportStepper from '@/Pages/CRM/Import/Components/ImportStepper.vue'
import AddEditTypeModal from '@/Pages/CRM/Settings/Components/AddEditTypeModal.vue'
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue'
import {
    IconUpload, IconFileSpreadsheet, IconCheck, IconChevronDown,
    IconInfoCircle, IconCirclePlus, IconSettings,
} from '@tabler/icons-vue'

const props = defineProps({
    contactTypes: { type: Array, required: true },
    propertyGroups: { type: Array, default: () => [] },
    error: { type: String, default: null },
})

const $t = useTranslation()

const isDragging = ref(false)
const showTypeModal = ref(false)

const importHints = [
    'Upload an Excel or CSV file. The first row must contain column headings.',
    'If your file mixes different contact types, activate the type column option: you pick the column that holds the type and assign each of its values to a contact type.',
    'Afterwards you map the Excel columns to the artwork properties of the contact type. Which properties are available is defined by the property groups assigned to the type in the CRM settings.',
    'Every contact needs a name: map a column to "Name" — or to first and last name, then the name is generated automatically.',
]

const form = useForm({
    file: null,
    crm_contact_type_id: props.contactTypes.length === 1 ? props.contactTypes[0].id : null,
    use_type_column: false,
})

const selectedType = computed(() =>
    props.contactTypes.find(t => t.id === form.crm_contact_type_id) ?? null
)

const canSubmit = computed(() => {
    if (!form.file) return false
    if (form.use_type_column) return true
    return !!form.crm_contact_type_id
})

const onFileChange = (e) => {
    form.file = e.target.files[0] ?? null
}

const onDrop = (e) => {
    isDragging.value = false
    const file = e.dataTransfer.files[0]
    if (file) {
        form.file = file
    }
}

const submit = () => {
    form.post(route('crm.import.upload'), {
        forceFormData: true,
    })
}
</script>
