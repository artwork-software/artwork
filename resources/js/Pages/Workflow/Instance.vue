<template>
    <AppLayout :title="'Workflow Instance #' + instance.id">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $t('Workflow Instance') }} #{{ instance.id }}
                </h2>
                <Link :href="route('workflow.index')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                    {{ $t('Back to Workflows') }}
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Workflow Instance Details -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('Workflow Instance Details') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $t('Workflow Definition') }}</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ instance.workflow_definition_config.workflow_definition.name }}
                                    <span class="text-xs text-gray-500">({{ instance.workflow_definition_config.workflow_definition.type }})</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $t('Subject') }}</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ instance.subject_type.split('\\').pop() }}
                                    <span class="text-xs text-gray-500">(ID: {{ instance.subject_id }})</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $t('Current Place') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ instance.current_place }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $t('Status') }}</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="instance.completed_at ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                                        {{ instance.completed_at ? $t('Completed') : $t('In Progress') }}
                                    </span>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $t('Created At') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ new Date(instance.created_at).toLocaleString() }}</p>
                            </div>
                            <div v-if="instance.completed_at">
                                <p class="text-sm font-medium text-gray-500">{{ $t('Completed At') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ new Date(instance.completed_at).toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow Visualization -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('Workflow Visualization') }}</h3>
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <workflow-diagram
                                :config="instance.workflow_definition_config.config"
                                :current-place="instance.current_place"
                            />
                        </div>
                    </div>

                    <!-- Available Transitions -->
                    <div v-if="availableTransitions.length > 0 && !instance.completed_at" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('Available Transitions') }}</h3>
                        <div class="flex flex-wrap gap-2">
                            <form v-for="transition in availableTransitions" :key="transition.name" @submit.prevent="executeTransition(transition.name)">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-artwork-buttons-create border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-artwork-buttons-hover active:bg-artwork-buttons-hover focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                                    {{ transition.name }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Workflow History -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('Workflow History') }}</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $t('Transition') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $t('From') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $t('To') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $t('Timestamp') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="log in instance.workflow_logs" :key="log.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ log.transition }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ log.from_place }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ log.to_place }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ new Date(log.created_at).toLocaleString() }}</div>
                                        </td>
                                    </tr>
                                    <tr v-if="instance.workflow_logs.length === 0">
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            {{ $t('No workflow history found') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { defineComponent } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import WorkflowDiagram from './Components/WorkflowDiagram.vue'

export default defineComponent({
    components: {
        AppLayout,
        Link,
        WorkflowDiagram
    },
    props: {
        instance: {
            type: Object,
            required: true
        },
        availableTransitions: {
            type: Array,
            required: true
        }
    },
    setup(props) {
        const form = useForm({
            transition: ''
        })

        const executeTransition = (transitionName) => {
            form.transition = transitionName
            form.post(route('workflow.instances.execute-transition', props.instance.id), {
                preserveScroll: true
            })
        }

        return {
            executeTransition
        }
    }
})
</script>
