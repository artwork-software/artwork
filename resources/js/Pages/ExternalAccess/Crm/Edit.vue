<template>
    <ExternalAppLayout :title="$t('My data')">
        <div class="px-4 py-6 sm:px-8 sm:py-10 max-w-3xl">
            <h1 class="text-xl sm:text-2xl font-bold text-text">{{ $t('My data') }}</h1>

            <p v-if="pendingSubmission" class="mt-4 rounded-xl border border-warning-border bg-warning-surface px-4 py-3 text-sm text-warning">
                {{ $t('You have a pending submission from {date}.', { date: formatDate(pendingSubmission.submitted_at) }) }}
                {{ $t('You can update it; the previous version will be replaced.') }}
            </p>

            <form @submit.prevent="submit" class="mt-8 space-y-10">
                <section v-for="section in schema.sections" :key="section.key">
                    <header class="mb-4">
                        <h2 class="text-lg font-semibold">{{ section.label }}</h2>
                        <p class="text-xs text-text-subtle mt-1">
                            {{ $t('Changes in this section will be reviewed before they take effect.') }}
                        </p>
                    </header>

                    <div class="space-y-4">
                        <div v-for="field in section.fields" :key="field.key">
                            <label :for="`${section.key}-${field.key}`" class="block text-sm font-medium text-text-muted">
                                {{ field.label }}
                                <span v-if="field.required" class="text-danger">*</span>
                            </label>

                            <!-- Upload-Eigenschaft: Datei geht vorläufig mit der Einreichung mit -->
                            <div v-if="field.inputType === 'file'" class="mt-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-2 rounded-lg border border-border-subtle bg-white px-3 py-2 text-sm">
                                    <IconFile class="size-4 shrink-0 text-text-subtle" aria-hidden="true" />
                                    <span v-if="hasNewFile(section, field)" class="min-w-0 flex-1 break-all text-text">
                                        {{ fileChangeOf(section, field).name }}
                                        <span class="text-xs text-text-subtle">({{ $t('new, will be submitted for review') }})</span>
                                    </span>
                                    <span v-else-if="fileChangeOf(section, field) === REMOVE" class="min-w-0 flex-1 break-all text-text-subtle line-through">
                                        {{ field.value }}
                                    </span>
                                    <span v-else-if="field.value" class="min-w-0 flex-1 break-all text-text">{{ field.value }}</span>
                                    <span v-else class="min-w-0 flex-1 text-text-subtle">{{ $t('No file') }}</span>
                                </div>

                                <div v-if="fileUpload.enabled" class="flex flex-wrap items-center gap-3">
                                    <label
                                        :for="`${section.key}-${field.key}`"
                                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border px-3 py-2 text-sm font-medium text-text hover:border-border-strong"
                                    >
                                        <IconUpload class="size-4" aria-hidden="true" />
                                        {{ field.value || hasNewFile(section, field) ? $t('Replace file') : $t('Select file') }}
                                        <input
                                            :id="`${section.key}-${field.key}`"
                                            type="file"
                                            class="sr-only"
                                            :accept="fileUpload.accept"
                                            @change="onFileChosen(section, field, $event)"
                                        />
                                    </label>
                                    <button
                                        v-if="fileChangeOf(section, field) !== undefined"
                                        type="button"
                                        class="text-sm text-text-muted underline"
                                        @click="resetFileChange(section, field)"
                                    >
                                        {{ $t('Undo') }}
                                    </button>
                                    <button
                                        v-else-if="field.value && !field.required"
                                        type="button"
                                        class="text-sm text-danger underline"
                                        @click="setFileChange(section, field, REMOVE)"
                                    >
                                        {{ $t('Remove file') }}
                                    </button>
                                </div>
                                <p v-if="fileUpload.enabled" class="text-xs text-text-subtle">
                                    {{ $t('Allowed: {types}, up to {size} MB. The file is only saved after review.', { types: allowedTypesLabel, size: maxMegabytes }) }}
                                </p>
                                <p v-else class="text-xs text-text-subtle">
                                    {{ $t('Uploading files is not enabled for external accesses. You can see the name of the current file.') }}
                                </p>
                                <p v-if="fileErrors[fileKey(section, field)]" class="text-xs text-danger">
                                    {{ fileErrors[fileKey(section, field)] }}
                                </p>
                            </div>
                            <textarea
                                v-else-if="field.inputType === 'textarea'"
                                :id="`${section.key}-${field.key}`"
                                v-model="form.values[section.key][field.key]"
                                class="mt-1 block w-full rounded-lg border border-border bg-white px-3 py-2 text-base sm:text-sm focus:border-accent-600 focus:outline-none focus:ring-1 focus:ring-accent-600"
                                rows="3"
                            />
                            <!-- Wie intern (CrmPropertyValueInput): gespeichert als '1'/'0' -->
                            <label v-else-if="field.inputType === 'checkbox'" class="mt-1 inline-flex items-center gap-2">
                                <input
                                    :id="`${section.key}-${field.key}`"
                                    type="checkbox"
                                    :checked="form.values[section.key][field.key] === '1'"
                                    class="size-5 rounded border-border sm:size-4"
                                    @change="form.values[section.key][field.key] = $event.target.checked ? '1' : '0'"
                                />
                            </label>
                            <select
                                v-else-if="field.inputType === 'select'"
                                :id="`${section.key}-${field.key}`"
                                v-model="form.values[section.key][field.key]"
                                class="mt-1 block w-full rounded-lg border border-border bg-white px-3 py-2 text-base sm:text-sm focus:border-accent-600 focus:outline-none focus:ring-1 focus:ring-accent-600"
                            >
                                <option value="">{{ $t('Please select') }}</option>
                                <option v-for="option in field.options" :key="option" :value="option">{{ option }}</option>
                            </select>
                            <!-- url als Textfeld wie intern: type="url" lehnt Adressen ohne https:// ab -->
                            <input
                                v-else
                                :id="`${section.key}-${field.key}`"
                                :type="field.inputType === 'url' ? 'text' : field.inputType"
                                :inputmode="field.inputType === 'url' ? 'url' : undefined"
                                v-model="form.values[section.key][field.key]"
                                class="mt-1 block w-full rounded-lg border border-border bg-white px-3 py-2 text-base sm:text-sm focus:border-accent-600 focus:outline-none focus:ring-1 focus:ring-accent-600"
                            />

                            <p v-if="errors[`values.${section.key}.${field.key}`]" class="mt-1 text-xs text-danger">
                                {{ errors[`values.${section.key}.${field.key}`] }}
                            </p>
                        </div>
                    </div>
                </section>

                <div class="flex justify-end gap-3 pt-4 border-t border-border-subtle">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-surface-inverse px-4 py-3 text-base font-medium text-white disabled:bg-border-strong disabled:cursor-not-allowed sm:w-auto sm:py-2 sm:text-sm"
                    >
                        {{ $t('Submit changes for review') }}
                    </button>
                </div>
            </form>
        </div>
    </ExternalAppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { IconFile, IconUpload } from '@tabler/icons-vue'
import ExternalAppLayout from '@/Pages/ExternalAccess/Layouts/ExternalAppLayout.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const props = defineProps({
    schema: { type: Object, required: true },
    pendingSubmission: { type: Object, default: null },
    fileUpload: { type: Object, default: () => ({ enabled: false, accept: '', max_kilobytes: 0 }) },
})

const $t = useTranslation()
const page = usePage()
const errors = computed(() => page.props.errors ?? {})

// Datei-Felder: nur geänderte gehen mit (File = neue Datei, REMOVE = Datei entfernen)
const REMOVE = 'remove'
const fileChanges = reactive({})
const fileErrors = reactive({})

const initialValues = {}
for (const section of props.schema.sections) {
    initialValues[section.key] = {}
    for (const field of section.fields) {
        if (field.inputType === 'file') {
            continue
        }
        initialValues[section.key][field.key] = field.value ?? ''
    }
}

const form = useForm({ values: initialValues })

const maxMegabytes = computed(() => Math.round((props.fileUpload.max_kilobytes ?? 0) / 1024))
const allowedTypesLabel = computed(() => (props.fileUpload.accept ?? '')
    .split(',')
    .map((extension) => extension.replace('.', '').toUpperCase())
    .filter(Boolean)
    .join(', '))

function fileKey(section, field) {
    return `${section.key}|${field.key}`
}

function fileChangeOf(section, field) {
    return fileChanges[fileKey(section, field)]
}

function hasNewFile(section, field) {
    return fileChangeOf(section, field) instanceof File
}

function setFileChange(section, field, change) {
    fileChanges[fileKey(section, field)] = change
    delete fileErrors[fileKey(section, field)]
}

function resetFileChange(section, field) {
    delete fileChanges[fileKey(section, field)]
    delete fileErrors[fileKey(section, field)]
}

function onFileChosen(section, field, event) {
    const file = event.target.files?.[0]
    event.target.value = ''
    if (!file) {
        return
    }
    const maxBytes = (props.fileUpload.max_kilobytes ?? 0) * 1024
    if (maxBytes > 0 && file.size > maxBytes) {
        fileErrors[fileKey(section, field)] = $t('The file is larger than {size} MB.', { size: maxMegabytes.value })
        return
    }
    setFileChange(section, field, file)
}

function submit() {
    form
        .transform((data) => {
            const values = {}
            for (const section of props.schema.sections) {
                values[section.key] = { ...(data.values[section.key] ?? {}) }
                for (const field of section.fields) {
                    const change = fileChangeOf(section, field)
                    if (field.inputType !== 'file' || change === undefined) {
                        continue
                    }
                    values[section.key][field.key] = change === REMOVE ? '' : change
                }
            }
            return { values }
        })
        .post(route('external.crm.submit'))
}

function formatDate(iso) {
    return new Date(iso).toLocaleDateString()
}
</script>
