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

                            <textarea
                                v-if="field.inputType === 'textarea'"
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
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import ExternalAppLayout from '@/Pages/ExternalAccess/Layouts/ExternalAppLayout.vue'

const props = defineProps({
    schema: { type: Object, required: true },
    pendingSubmission: { type: Object, default: null },
})

const page = usePage()
const errors = computed(() => page.props.errors ?? {})

const initialValues = {}
for (const section of props.schema.sections) {
    initialValues[section.key] = {}
    for (const field of section.fields) {
        initialValues[section.key][field.key] = field.value ?? ''
    }
}

const form = useForm({ values: initialValues })

function submit() {
    form.post(route('external.crm.submit'))
}

function formatDate(iso) {
    return new Date(iso).toLocaleDateString()
}
</script>
