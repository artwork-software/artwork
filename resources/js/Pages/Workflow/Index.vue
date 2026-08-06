<template>
    <AppLayout title="Workflows">
        <template #header>
            <h2 class="font-semibold text-xl text-text leading-tight">
                {{ $t('Workflows') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Workflow Definitions -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-text">{{ $t('Workflow Definitions') }}</h3>
                        <Link :href="route('workflow.definitions.create')" class="inline-flex items-center px-4 py-2 bg-accent-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-accent-700 active:bg-accent-700 focus:outline-none focus:border-border-strong focus:ring focus:ring-border disabled:bg-surface-canvas disabled:border-border-subtle disabled:text-text-subtle disabled:cursor-not-allowed transition">
                            {{ $t('Create New Definition') }}
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border-subtle">
                            <thead class="bg-surface-sunken">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                        {{ $t('Name') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                        {{ $t('Type') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                        {{ $t('Status') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                        {{ $t('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-border-subtle">
                                <tr v-for="definition in definitions" :key="definition.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-text">{{ definition.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-text-subtle">{{ definition.type }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="definition.is_active ? 'bg-success-surface text-success' : 'bg-danger-surface text-danger'">
                                            {{ definition.is_active ? $t('Active') : $t('Inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <Link :href="route('workflow.definitions.show', definition.id)" class="text-accent-600 hover:text-accent-700 mr-3">
                                            {{ $t('View') }}
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="definitions.length === 0">
                                    <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle text-center">
                                        {{ $t('No workflow definitions found') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Workflow Instances -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-text mb-4">{{ $t('Workflow Instances') }}</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border-subtle">
                            <thead class="bg-surface-sunken">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                        {{ $t('Workflow') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                        {{ $t('Subject') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                        {{ $t('Current Place') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                        {{ $t('Status') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                        {{ $t('Created') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                        {{ $t('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-border-subtle">
                                <tr v-for="instance in instances" :key="instance.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-text">
                                            {{ instance.workflow_definition_config.workflow_definition.name }}
                                        </div>
                                        <div class="text-xs text-text-subtle">
                                            {{ instance.workflow_definition_config.workflow_definition.type }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-text">{{ instance.subject_type.split('\\').pop() }}</div>
                                        <div class="text-xs text-text-subtle">ID: {{ instance.subject_id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-text">{{ instance.current_place }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="instance.completed_at ? 'bg-success-surface text-success' : 'bg-accent-100 text-accent-700'">
                                            {{ instance.completed_at ? $t('Completed') : $t('In Progress') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-text-subtle">{{ new Date(instance.created_at).toLocaleString() }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <Link :href="route('workflow.instances.show', instance.id)" class="text-accent-600 hover:text-accent-700">
                                            {{ $t('View') }}
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="instances.length === 0">
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle text-center">
                                        {{ $t('No workflow instances found') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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

export default defineComponent({
    components: {
        AppLayout,
        Link
    },
    props: {
        definitions: {
            type: Array,
            required: true
        },
        instances: {
            type: Array,
            required: true
        }
    }
})
</script>
