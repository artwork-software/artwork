<template>
    <ArtworkBaseModal
        :title="materialSet?.id ? $t('Edit Material Set') : $t('Create Material Set')"
        :description="materialSet?.id ? $t('Edit the material set details') : $t('Create a new material set')"
        @close="$emit('close')"
    >

        <form @submit.prevent="submit">
            <!-- Material Set Name -->
            <div class="space-y-4">
                <BaseInput
                    v-model="materialSetForm.name"
                    id="material-set-name"
                    :label="$t('Name')"
                    required
                />

                <BaseTextarea
                    v-model="materialSetForm.description"
                    id="material-set-description"
                    :label="$t('Description')"
                />

                <ArticleSearch @article-selected="addItemToSet" />

                <div class="mt-4">
                    <h3 class="text-lg font-semibold mb-2">{{ $t('Articles in Set') }}</h3>
                    <div class="inline-block min-w-full py-2 align-middle">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead>
                            <tr class="divide-x divide-gray-200">
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">{{ $t('Name')}}</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Quantity')}}</th>
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">{{ $t('Actions')}}</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="item in materialSetForm.items" :key="item.id" class="divide-x divide-gray-200">
                                <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0">{{ item.name }}</td>
                                <td class="p-4 text-sm text-gray-500">
                                    <input type="text" v-model="item.quantity"
                                           required
                                           class="block rounded-md bg-white border-none text-xs px-3 py-1.5 text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                                           placeholder="Name"
                                    />
                                </td>
                                <td class="py-4 pr-4 pl-4 text-sm whitespace-nowrap text-gray-500 sm:pr-0 flex items-center justify-center">
                                    <button type="button" @click="materialSetForm.items = materialSetForm.items.filter(i => i.id !== item.id)" class="text-red-600 hover:underline text-center">
                                        <component :is="IconTrash" class="size-4" />
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <ArtworkBaseModalButton variant="primary" type="submit">
                    {{ materialSet?.id ? $t('Update Material Set') : $t('Create Material Set') }}
                </ArtworkBaseModalButton>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArticleSearch from "@/Components/SearchBars/ArticleSearch.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    materialSet: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            name: '',
            description: '',
            items: []
        })
    }
})

const materialSetForm = useForm({
    id: props.materialSet.id || null,
    name: props.materialSet.name || '',
    description: props.materialSet.description || '',
    items: props.materialSet.items.map((articleDetail) => {
        return {
            id: articleDetail.article.id,
            name: articleDetail.article.name,
            quantity: articleDetail.quantity || 1 // Default quantity to 1 if not specified
        };
    }) || []
})

const addItemToSet = (article) => {
    // add article to the material set items with quantity 1
    const existingItem = materialSetForm.items.find(item => item.id === article.id);
    if (existingItem) {
        existingItem.quantity += 1; // Increment quantity if already exists
    } else {
        materialSetForm.items.push({ ...article, quantity: 1 });
    }
};

const emit = defineEmits(['close']);


const submit = () => {
    if(props.materialSet.id && materialSetForm.id) {
        materialSetForm.patch(route('material-sets.update', props.materialSet.id), {
            onSuccess: () => {
                emit('close');
            }
        });
    } else {
        materialSetForm.post(route('material-sets.store'), {
            onSuccess: () => {
                emit('close');
            }
        });
    }
};
</script>

<style scoped>

</style>
