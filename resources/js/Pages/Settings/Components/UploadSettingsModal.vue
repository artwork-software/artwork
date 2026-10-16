<script setup>
import { computed, reactive, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'

/**
 * Einstellungen eines Upload-Bereichs der Dateiupload-Komponente (Tab- oder Ordner-Platzierung):
 * erlaubte Dateitypen (innerhalb der globalen Freigaben), max. Dateigröße, max. Anzahl Dateien je Projekt.
 */
const props = defineProps({
    placementId: { type: [Number, String], required: true },
    inFolder: { type: Boolean, default: false },
    initialSettings: { type: Object, default: null },
})

const emit = defineEmits(['close'])

const page = usePage()
// Global freigegebene Endungen und Maximalgröße (TabController::index → documentUploadLimits)
const globalLimits = computed(() => page.props.documentUploadLimits ?? { extensions: [], max_file_size_mb: 0 })

const form = reactive({
    allowed_extensions: [...(props.initialSettings?.allowed_extensions ?? [])],
    max_file_size_mb: props.initialSettings?.max_file_size_mb ?? '',
    max_files: props.initialSettings?.max_files ?? '',
})
const errors = ref({})
const saving = ref(false)

const restrictTypes = ref(form.allowed_extensions.length > 0)

function toggleExtension(extension, checked) {
    form.allowed_extensions = checked
        ? [...form.allowed_extensions, extension]
        : form.allowed_extensions.filter((value) => value !== extension)
}

function save() {
    saving.value = true
    errors.value = {}
    const routeName = props.inFolder
        ? 'tab.update.disclosure-component.upload-settings'
        : 'tab.update.component.upload-settings'
    const params = props.inFolder ? { disclosureComponent: props.placementId } : { componentInTab: props.placementId }
    router.patch(route(routeName, params), {
        allowed_extensions: restrictTypes.value ? form.allowed_extensions : [],
        max_file_size_mb: form.max_file_size_mb === '' ? null : Number(form.max_file_size_mb),
        max_files: form.max_files === '' ? null : Number(form.max_files),
    }, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
        onError: (validationErrors) => { errors.value = validationErrors },
        onFinish: () => { saving.value = false },
    })
}
</script>

<template>
    <ArtworkBaseModal
        modal-size="sm:max-w-2xl"
        :title="$t('Upload settings')"
        :description="$t('Limits for this upload area. They apply in addition to the global file settings.')"
        @close="emit('close')"
    >
        <div class="space-y-5">
            <div>
                <label class="flex items-center gap-2 text-sm text-text">
                    <input v-model="restrictTypes" type="checkbox" class="input-checklist" />
                    {{ $t('Only allow certain file types') }}
                </label>
                <div v-if="restrictTypes" class="mt-2 max-h-56 overflow-auto rounded-lg border border-border-subtle p-3">
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                        <label
                            v-for="extension in globalLimits.extensions"
                            :key="extension"
                            class="flex items-center gap-1.5 text-sm text-text-muted"
                        >
                            <input
                                type="checkbox"
                                class="input-checklist"
                                :checked="form.allowed_extensions.includes(extension)"
                                @change="toggleExtension(extension, $event.target.checked)"
                            />
                            .{{ extension }}
                        </label>
                    </div>
                </div>
                <p class="mt-1 text-xs text-text-subtle">
                    {{ $t('Only types that are allowed in the global file settings can be selected.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput
                    id="upload_max_file_size_mb"
                    v-model="form.max_file_size_mb"
                    type="number"
                    :min="1"
                    :label="$t('Maximum file size (MB)')"
                    :error="errors.max_file_size_mb"
                />
                <BaseInput
                    id="upload_max_files"
                    v-model="form.max_files"
                    type="number"
                    :min="1"
                    :label="$t('Maximum number of files per project')"
                    :error="errors.max_files"
                />
            </div>
            <p class="text-xs text-text-subtle">
                {{ $t('Leave empty for no additional limit. The global maximum file size is {size} MB.', { size: globalLimits.max_file_size_mb }) }}
            </p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <BaseUIButton type="button" hide-icon @click="emit('close')">{{ $t('Cancel') }}</BaseUIButton>
                <BaseUIButton type="button" variant="primary" hide-icon :disabled="saving" @click="save">{{ $t('Save') }}</BaseUIButton>
            </div>
        </div>
    </ArtworkBaseModal>
</template>
