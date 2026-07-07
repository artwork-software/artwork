<template>
    <ExternalAppLayout :title="$t('My data')">
        <div class="px-8 py-10 max-w-3xl">
            <h1 class="text-2xl font-bold text-zinc-900">{{ $t('My data') }}</h1>

            <p v-if="pendingSubmission" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ $t('You have a pending submission from {date}.', { date: formatDate(pendingSubmission.submitted_at) }) }}
                {{ $t('You can update it; the previous version will be replaced.') }}
            </p>

            <form @submit.prevent="submit" class="mt-8 space-y-10">
                <section v-for="section in schema.sections" :key="section.key">
                    <header class="mb-4">
                        <h2 class="text-lg font-semibold">{{ section.label }}</h2>
                        <p v-if="section.mode === 'staged'" class="text-xs text-zinc-500 mt-1">
                            {{ $t('Changes in this section will be reviewed before they take effect.') }}
                        </p>
                        <p v-else class="text-xs text-zinc-500 mt-1">
                            {{ $t('Changes in this section are saved immediately.') }}
                        </p>
                    </header>

                    <div class="space-y-4">
                        <div v-for="field in section.fields" :key="field.key">
                            <label :for="`${section.key}-${field.key}`" class="block text-sm font-medium text-zinc-700">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-500">*</span>
                            </label>

                            <textarea
                                v-if="field.inputType === 'textarea'"
                                :id="`${section.key}-${field.key}`"
                                v-model="form.values[section.key][field.key]"
                                class="mt-1 block w-full rounded-lg border-zinc-300 text-sm"
                                rows="3"
                            />
                            <label v-else-if="field.inputType === 'checkbox'" class="mt-1 inline-flex items-center gap-2">
                                <input
                                    :id="`${section.key}-${field.key}`"
                                    type="checkbox"
                                    v-model="form.values[section.key][field.key]"
                                    class="rounded border-zinc-300"
                                />
                            </label>
                            <input
                                v-else
                                :id="`${section.key}-${field.key}`"
                                :type="field.inputType"
                                v-model="form.values[section.key][field.key]"
                                class="mt-1 block w-full rounded-lg border-zinc-300 text-sm"
                            />

                            <p v-if="errors[`values.${section.key}.${field.key}`]" class="mt-1 text-xs text-red-600">
                                {{ errors[`values.${section.key}.${field.key}`] }}
                            </p>
                        </div>
                    </div>
                </section>

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                    >
                        {{ $t('Save changes') }}
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
