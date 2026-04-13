<template>
    <AppLayout :title="$t('Import contacts')">
        <div class="mt-5 mx-auto container pb-20">
            <ToolbarHeader
                :icon="IconUpload"
                :title="$t('Import contacts')"
                icon-bg-class="bg-indigo-600/10 text-indigo-700"
            >
                <template #actions>
                    <Link :href="route('crm.index')" class="ui-button">
                        {{ $t('Back to CRM') }}
                    </Link>
                </template>
            </ToolbarHeader>

            <!-- Step Indicator -->
            <div class="mt-6 mb-8">
                <div class="flex items-center space-x-3">
                    <span class="flex items-center justify-center size-8 rounded-full bg-indigo-600 text-white text-sm font-bold">1</span>
                    <span class="text-sm font-medium text-gray-900">{{ $t('Upload file') }}</span>
                    <div class="flex-1 h-px bg-gray-300"></div>
                    <span class="flex items-center justify-center size-8 rounded-full bg-gray-200 text-gray-500 text-sm font-bold">2</span>
                    <span class="text-sm text-gray-500">{{ $t('Map columns') }}</span>
                </div>
            </div>

            <!-- Error -->
            <div v-if="error" class="mb-6 rounded-md bg-red-50 p-4">
                <p class="text-sm font-medium text-red-800">{{ error }}</p>
            </div>

            <form @submit.prevent="submit" class="max-w-xl space-y-6">
                <!-- Contact Type -->
                <div>
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
                                        v-for="type in contactTypes"
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
                    <p v-if="form.errors.crm_contact_type_id" class="mt-1 text-sm text-red-600">
                        {{ form.errors.crm_contact_type_id }}
                    </p>
                </div>

                <!-- File Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        {{ $t('Upload file') }}
                    </label>
                    <div
                        class="mt-1 flex justify-center rounded-lg border-2 border-dashed px-6 py-10 transition-colors"
                        :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300'"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="onDrop"
                    >
                        <div class="text-center">
                            <component :is="IconFileSpreadsheet" class="mx-auto h-12 w-12 text-gray-400" />
                            <div class="mt-4 flex text-sm leading-6 text-gray-600">
                                <label
                                    for="file-upload"
                                    class="relative cursor-pointer rounded-md font-semibold text-indigo-600 hover:text-indigo-500"
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
                            <p class="text-xs leading-5 text-gray-500">
                                {{ $t('Supported formats: CSV, XLSX, XLS') }} (max. 10 MB)
                            </p>
                            <p v-if="form.file" class="mt-3 text-sm font-medium text-indigo-600">
                                {{ form.file.name }}
                            </p>
                        </div>
                    </div>
                    <p v-if="form.errors.file" class="mt-1 text-sm text-red-600">
                        {{ form.errors.file }}
                    </p>
                </div>

                <!-- Submit -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="!canSubmit || form.processing"
                        class="ui-button-add disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            {{ $t('Uploading') }}...
                        </span>
                        <span v-else>{{ $t('Next: Map columns') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { useTranslation } from '@/Composeables/Translation.js'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue'
import { IconUpload, IconFileSpreadsheet, IconCheck, IconChevronDown } from '@tabler/icons-vue'

const props = defineProps({
    contactTypes: { type: Array, required: true },
    error: { type: String, default: null },
})

const $t = useTranslation()

const isDragging = ref(false)

const form = useForm({
    file: null,
    crm_contact_type_id: props.contactTypes.length === 1 ? props.contactTypes[0].id : null,
})

const selectedType = computed(() =>
    props.contactTypes.find(t => t.id === form.crm_contact_type_id) ?? null
)

const canSubmit = computed(() => form.file && form.crm_contact_type_id)

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
