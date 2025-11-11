<template>
    <AppLayout :title="definition.name">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ definition.name }}
                </h2>
                <Link :href="route('workflow.index')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                    {{ $t('Back to Workflows') }}
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Workflow Definition Details -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('Workflow Definition Details') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $t('Name') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ definition.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $t('Type') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ definition.type }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $t('Status') }}</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="definition.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                        {{ definition.is_active ? $t('Active') : $t('Inactive') }}
                                    </span>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $t('Created At') }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ new Date(definition.created_at).toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow Visualization -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('Workflow Visualization') }}</h3>
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <workflow-diagram :config="definition.current_config.config" />
                        </div>
                    </div>

                    <!-- Workflow Configuration -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('Workflow Configuration') }}</h3>
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <pre class="text-sm text-gray-800 overflow-auto">{{ JSON.stringify(definition.current_config.config, null, 2) }}</pre>
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
import { Link } from '@inertiajs/vue3'
import WorkflowDiagram from './Components/WorkflowDiagram.vue'

export default defineComponent({
    components: {
        AppLayout,
        Link,
        WorkflowDiagram
    },
    props: {
        definition: {
            type: Object,
            required: true
        }
    }
})
</script>
