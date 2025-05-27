<template>
    <AppLayout :title="$t('Material Sets')">
        <div class="space-y-6 artwork-container">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-800">{{ $t('Material Sets') }}</h1>

                <BaseButton :text="$t('New Material Set')" @click="showCreateOrUpdateMaterialSetModal = true">
                    <component is="IconCopyPlus" class="size-5 mr-2" />
                </BaseButton>
            </div>

            <div class="mt-8 flow-root">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead>
                            <tr class="divide-x divide-gray-200">
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">
                                    {{ $t('Name') }}
                                </th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    {{ $t('Description') }}
                                </th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    {{ $t('Articles') }}
                                </th>
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">
                                    {{ $t('Actions') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <SingleMaterialSet v-for="set in materialSets" :key="set.id" :set="set" />
                                <tr v-if="materialSets.length === 0">
                                    <td colspan="4" class="text-center text-sm text-gray-500 py-6"><BaseAlertComponent message="No material sets found." type="error" use-translation /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <pre>
            {{ materialSets }}
        </pre>

        <CreateOrUpdateMaterialSetModal
            v-if="showCreateOrUpdateMaterialSetModal"
            @close="showCreateOrUpdateMaterialSetModal = false"
            />
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import CreateOrUpdateMaterialSetModal from "@/Pages/MaterialSet/Components/CreateOrUpdateMaterialSetModal.vue";
import {ref} from "vue";
import SingleMaterialSet from "@/Pages/MaterialSet/Components/SingleMaterialSet.vue";

const props = defineProps({
    materialSets: {
        type: Object,
        required: true
    }
})

const showCreateOrUpdateMaterialSetModal = ref(false);
</script>

<style scoped>

</style>