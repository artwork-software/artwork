<template>
    <AppLayout title="Create Workflow Definition">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $t('Create Workflow Definition') }}
                </h2>
                <Link :href="route('workflow.index')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                    {{ $t('Back to Workflows') }}
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <!-- Basic Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('Basic Information') }}</h3>

                            <div class="grid grid-cols-1 gap-6">
                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">{{ $t('Name') }}</label>
                                    <div class="mt-1">
                                        <input
                                            type="text"
                                            id="name"
                                            v-model="form.name"
                                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                            :class="{ 'border-red-500': form.errors.name }"
                                        />
                                    </div>
                                    <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                                </div>

                                <!-- Type -->
                                <div>
                                    <label for="type" class="block text-sm font-medium text-gray-700">{{ $t('Type') }}</label>
                                    <div class="mt-1">
                                        <input
                                            type="text"
                                            id="type"
                                            v-model="form.type"
                                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                            :class="{ 'border-red-500': form.errors.type }"
                                        />
                                    </div>
                                    <p v-if="form.errors.type" class="mt-2 text-sm text-red-600">{{ form.errors.type }}</p>
                                    <p class="mt-2 text-sm text-gray-500">{{ $t('The type is used to identify the workflow (e.g., "approval", "review")') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Workflow Configuration -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('Workflow Configuration') }}</h3>

                            <div class="mb-4">
                                <p class="text-sm text-gray-500">{{ $t('Define the workflow configuration in JSON format. The configuration should include places, transitions, and an initial place.') }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="config" class="block text-sm font-medium text-gray-700">{{ $t('Configuration (JSON)') }}</label>
                                <div class="mt-1">
                                    <textarea
                                        id="config"
                                        v-model="configJson"
                                        rows="15"
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md font-mono"
                                        :class="{ 'border-red-500': form.errors.config }"
                                    ></textarea>
                                </div>
                                <p v-if="form.errors.config" class="mt-2 text-sm text-red-600">{{ form.errors.config }}</p>
                                <p v-if="jsonError" class="mt-2 text-sm text-red-600">{{ jsonError }}</p>
                            </div>

                            <div class="mb-4">
                                <button
                                    type="button"
                                    @click="useExampleConfig"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    {{ $t('Use Example Configuration') }}
                                </button>
                            </div>
                        </div>

                        <!-- Workflow Visualization Preview -->
                        <div v-if="isValidJson && parsedConfig" class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('Workflow Visualization Preview') }}</h3>
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <workflow-diagram :config="parsedConfig" />
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-artwork-buttons-create border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-artwork-buttons-hover active:bg-artwork-buttons-hover focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition"
                                :disabled="form.processing || !isValidJson"
                            >
                                {{ $t('Create Workflow Definition') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { defineComponent, ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import WorkflowDiagram from './Components/WorkflowDiagram.vue'

export default defineComponent({
    components: {
        AppLayout,
        Link,
        WorkflowDiagram
    },
    setup() {
        const form = useForm({
            name: '',
            type: '',
            config: {}
        })

        const configJson = ref(JSON.stringify({
            places: [],
            transitions: [],
            initial_place: ''
        }, null, 2))

        const jsonError = ref(null)
        const parsedConfig = ref(null)

        const isValidJson = computed(() => {
            try {
                parsedConfig.value = JSON.parse(configJson.value)
                jsonError.value = null
                return true
            } catch (error) {
                jsonError.value = error.message
                return false
            }
        })

        const useExampleConfig = () => {
            const exampleConfig = {
                places: [
                    { name: 'draft', type: 'start', label: 'Draft' },
                    { name: 'review', type: 'normal', label: 'Under Review' },
                    { name: 'approved', type: 'end', label: 'Approved' },
                    { name: 'rejected', type: 'end', label: 'Rejected' }
                ],
                transitions: [
                    { name: 'submit', from: ['draft'], to: 'review' },
                    { name: 'approve', from: ['review'], to: 'approved' },
                    { name: 'reject', from: ['review'], to: 'rejected' },
                    { name: 'revise', from: ['review'], to: 'draft' }
                ],
                initial_place: 'draft'
            }

            configJson.value = JSON.stringify(exampleConfig, null, 2)
        }

        const submit = () => {
            if (!isValidJson.value) {
                return
            }

            form.config = parsedConfig.value
            form.post(route('workflow.definitions.store'), {
                onSuccess: () => {
                    form.reset()
                    configJson.value = JSON.stringify({
                        places: [],
                        transitions: [],
                        initial_place: ''
                    }, null, 2)
                }
            })
        }

        return {
            form,
            configJson,
            jsonError,
            parsedConfig,
            isValidJson,
            useExampleConfig,
            submit
        }
    }
})
</script>
