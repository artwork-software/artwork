<template>
    <form @submit.prevent="submit" class="mx-4">
        <div class="grid gird-cols-1 md:grid-cols-2 gap-x-4 mb-4">
            <div>
                <div class="grid gird-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-full">
                        <BaseInput id="material_value" type="number" :step="0.01" v-model="externMaterialIssueForm.material_value" :label="$t('Material Value')" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.material_value">{{ externMaterialIssueForm.errors.material_value }}</p>
                    </div>

                    <div class="col-span-full">
                        <BaseInput id="issue_date" v-model="externMaterialIssueForm.issue_date" :label="$t('Issue Date')" type="date" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.external_email">{{ externMaterialIssueForm.errors.issue_date }}</p>
                    </div>
                    <div class="col-span-full">
                        <BaseInput id="return_date" v-model="externMaterialIssueForm.return_date" :label="$t('Return Date')" type="date" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.return_date">{{ externMaterialIssueForm.errors.return_date }}</p>
                    </div>
                    <div class="col-span-full">
                        <BaseInput id="external_name" v-model="externMaterialIssueForm.external_name" :label="$t('External Name')" type="text" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.external_name">{{ externMaterialIssueForm.errors.external_name }}</p>
                    </div>
                    <div class="col-span-full">
                        <BaseInput id="external_email" v-model="externMaterialIssueForm.external_email" :label="$t('External E-Mail')" type="text" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.external_email">{{ externMaterialIssueForm.errors.external_email }}</p>
                    </div>
                    <div class="col-span-full">
                        <BaseInput id="external_phone" v-model="externMaterialIssueForm.external_phone" :label="$t('External Phone')" type="text" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.external_phone">{{ externMaterialIssueForm.errors.external_phone }}</p>
                    </div>
                    <div class="col-span-full">
                        <BaseInput id="external_address" v-model="externMaterialIssueForm.external_address" :label="$t('External Address')" type="text" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.external_address">{{ externMaterialIssueForm.errors.external_address }}</p>
                    </div>


                    <div class="col-span-full">
                        <BaseTextarea id="return_remarks" v-model="externMaterialIssueForm.return_remarks" :label="$t('Defects after return')" />
                    </div>


                    <div class="col-span-full">
                        <button @click="$refs.externMaterialIssueFiles.click()" type="button" class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-12 text-center hover:border-gray-400 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden">
                            <component is="IconFile" class="mx-auto size-12 text-gray-400" stroke-width="1" />
                            <span class="mt-2 block text-sm font-semibold text-gray-900">{{ $t('File storage') }}</span>
                            <input
                                @change="upload"
                                class="hidden"
                                ref="externMaterialIssueFiles"
                                id="file"
                                type="file"
                                multiple
                            />
                        </button>
                        <div class="mt-4">
                            <div class="">
                                <div class="divide-y divide-gray-200 divide-dashed">
                                    <div class="py-2" v-for="(file, index) in externMaterialIssue.files" :key="index">
                                        <div class="flex items-center gap-x-4 justify-between">
                                            <div class="w-full">
                                                <h2 class="text-sm font-bold">
                                                    {{ file.original_name }}
                                                </h2>
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-end">
                                                    <button type="button" class="text-xs text-red-500" @click="removeFile(file.id)">
                                                        <component is="IconTrash" class="h-4 w-4" stroke-width="1.5"/>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="divide-y divide-gray-200 divide-dashed">
                                    <div class="py-2" v-for="(file, index) in externMaterialIssueForm.files" :key="index">
                                        <div class="flex items-center gap-x-4 justify-between">
                                            <div class="w-full">
                                                <h2 class="text-sm font-bold">
                                                    {{ file.name ?? file.original_name }}
                                                </h2>
                                                <p class="text-xs" v-if="file.size">
                                                    {{ (file.size / 1024 / 1024).toFixed(2) }} MB
                                                </p>
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-end">
                                                    <button type="button" class="text-xs text-red-500" @click="externMaterialIssueForm.files.splice(index, 1)">
                                                        <component is="IconTrash" class="h-4 w-4" stroke-width="1.5"/>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="flex items-center w-full gap-x-4">
                    <ArticleSearch @article-selected="addArticleToIssue" class="w-full"/>
                    <button type="button" @click="showArticleFilterModal = true" class="p-3 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <ToolTipComponent icon="IconListSearch" :tooltip-text="$t('Search for articles')" icon-size="size-7" tooltip-width="w-fit whitespace-nowrap" position="top" />
                    </button>
                    <button type="button" @click="showSelectMaterialSetModal = true" class="p-3 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ">
                        <ToolTipComponent icon="IconParentheses" :tooltip-text="$t('Select material sets')" icon-size="size-7" tooltip-width="w-fit whitespace-nowrap" position="top" />
                    </button>
                </div>
                <div class="mt-4">
                    <div class="bg-backgroundGray px-4 py-3 rounded-lg border border-gray-200 max-h-96 overflow-scroll">
                        <div class="divide-y divide-gray-200 divide-dashed" v-if="externMaterialIssueForm.articles.length > 0">
                            <div class="flex items-center gap-x-4 justify-between py-2 border-dashed" v-for="(article, index) in externMaterialIssueForm.articles" :key="index">
                                <div class="w-full">
                                    <h2 class="text-sm font-bold">
                                        {{ article.name }}
                                    </h2>
                                    <p class="text-xs">
                                        {{ article.description }}
                                    </p>
                                    <p class="text-xs flex items-center gap-x-1">{{ $t('Available stock for issue period') }}:
                                        <span v-if="!article.availableStockRequestIsLoading">{{ article.availableStock?.available }}</span>
                                        <span v-else class="flex items-center gap-x-1">{{ $t('Is queried') }}
                                            <component is="IconLoader" class="inline-block h-4 w-4 animate-spin text-gray-400" stroke-width="1.5"/>
                                        </span>
                                    </p>
                                    <p v-if="article.quantity > article.availableStock?.available">
                                        <span class="text-red-500 text-xs">
                                            {{ $t('You have selected more items than available in stock.') }}
                                        </span>
                                    </p>
                                </div>
                                <div class="w-28">
                                    <BaseInput :id="'article-quantity-' + index" type="number" v-model="article.quantity" :label="$t('Quantity')" />
                                </div>
                                <div class="flex items-center justify-end w-5">
                                    <button type="button" class="text-xs text-red-500" @click="removeArticle(index)">
                                        <component is="IconTrash" class="h-4 w-4" stroke-width="1.5"/>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <BaseAlertComponent :message="$t('No articles found')" type="info" class="text-center -mb-3"/>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between my-4">
                        <div>
                            <h2 class="font-lexend font-bold">{{ $t('Special article') }}</h2>
                        </div>
                        <button type="button" class="text-blue-500 underline text-xs font-lexend" @click="addSpecialItem">{{ $t('Add special article') }}</button>
                    </div>
                    <div class="bg-backgroundGray px-4 py-3 rounded-lg border border-gray-200 min-h-60 max-h-60 overflow-scroll">
                        <div class="divide-y divide-gray-200 divide-dashed">
                            <div class="py-2" v-for="(article, index) in externMaterialIssueForm.special_items" :key="index">
                                <div class="flex items-center gap-x-4 justify-between">
                                    <div class="w-full">
                                        <BaseInput :id="'special-article-name-' + index" type="text" v-model="article.name" :label="$t('Article Name')" />
                                    </div>
                                    <div class="w-28">
                                        <BaseInput :id="'special-article-quantity' + index" type="number" v-model="article.quantity" :label="$t('Quantity')" />
                                    </div>
                                    <div class="flex items-center justify-end">
                                        <button type="button" class="text-xs text-red-500" @click="removeSpecialArticle(index)">
                                            <component is="IconTrash" class="h-4 w-4" stroke-width="1.5"/>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-center w-full">
            <FormButton
                :text="externMaterialIssue?.id ? $t('Update') : $t('Save')"
                :disabled="externMaterialIssueForm.processing || !externMaterialIssueForm.issue_date || !externMaterialIssueForm.return_date || !externMaterialIssueForm.material_value"
                type="submit"
            />
        </div>
    </form>

    <ArticleSearchFilterModal
        v-if="showArticleFilterModal"
        @close="showArticleFilterModal = false"
        @add-article="addArticleToIssue"
    />

    <SelectMaterialSetModal
        v-if="showSelectMaterialSetModal"
        @close="showSelectMaterialSetModal = false"
        @add-material-set="addMaterialSetToIssue"
    />
</template>

<script setup>

import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import RoomSearch from "@/Components/SearchBars/RoomSearch.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import {XIcon} from "@heroicons/vue/outline";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArticleSearchFilterModal from "@/Pages/IssueOfMaterial/Components/ArticleSearchFilterModal.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {router, useForm} from "@inertiajs/vue3";
import {onMounted, ref, watch} from "vue";
import debounce from "lodash.debounce";
import ArticleSearch from "@/Components/SearchBars/ArticleSearch.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import SelectMaterialSetModal from "@/Pages/IssueOfMaterial/Components/SelectMaterialSetModal.vue";

const props = defineProps({
    externMaterialIssue: {
        type: Object,
        required: false,
        default: () => ({
            material_value: 0.00,
            issue_date: '',
            return_date: '',
            return_remarks: '',
            external_name: '',
            external_address: '',
            external_email: '',
            external_phone: '',
            files: [],
            articles: [],
            special_items: [],
        })
    },
})


const externMaterialIssueForm = useForm({
    id: props.externMaterialIssue.id ?? null,
    material_value: props.externMaterialIssue.material_value,
    issue_date: props.externMaterialIssue.issue_date,
    return_date: props.externMaterialIssue.return_date,
    return_remarks: props.externMaterialIssue.return_remarks,
    external_name: props.externMaterialIssue.external_name,
    external_address: props.externMaterialIssue.external_address,
    external_email: props.externMaterialIssue.external_email,
    external_phone: props.externMaterialIssue.external_phone,
    files: [],
    articles: props.externMaterialIssue?.articles || [],
    special_items: props.externMaterialIssue?.special_items || []
})

const showArticleFilterModal = ref(false)
const showSelectMaterialSetModal = ref(false)


const addSpecialItem = () => {
    externMaterialIssueForm.special_items.push({
        id: null,
        name: '',
        quantity: 1
    })
}

const removeSpecialArticle = (index) => {
    externMaterialIssueForm.special_items.splice(index, 1)
}

const addArticleToIssue = (article) => {
    const articleExists = externMaterialIssueForm.articles.find(a => a.id === article.id)
    if (!articleExists) {
        externMaterialIssueForm.articles.push({
            id: article.id,
            name: article.name,
            description: article.description,
            quantity: 1,
            availableStock: 0,
            availableStockRequestIsLoading: true,
        })
    }
    checkAvailableStock()
}

const addMaterialSetToIssue = (materialSet) => {
    const existingArticleIds = externMaterialIssueForm.articles.map(a => a.id)
    const newArticles = materialSet.items.filter(item => !existingArticleIds.includes(item.article.id)).map(item => ({
        id: item.article.id,
        name: item.article.name,
        description: item.article.description,
        quantity: item.quantity || 1,
        availableStock: 0,
        availableStockRequestIsLoading: true,
    }))
    externMaterialIssueForm.articles.push(...newArticles)
    checkAvailableStock()
}

const removeArticle = (index) => {
    externMaterialIssueForm.articles.splice(index, 1)
}
const emits = defineEmits(['close'])

const submit = () => {

    if(props.externMaterialIssue?.id){
        externMaterialIssueForm.patch(route('extern-issue-of-material.update', props.externMaterialIssue.id), {
            onSuccess: () => {
                emits('close')
            }
        })
    } else {
        externMaterialIssueForm.post(route('extern-issue-of-material.store'), {
            onSuccess: () => {
                emits('close')
            }
        })
    }
}


const checkAvailableStock = async () => {
    if (!externMaterialIssueForm.issue_date || !externMaterialIssueForm.return_date || externMaterialIssueForm.articles.length === 0) {
        return
    }

    const ids = externMaterialIssueForm.articles.map(a => a.id).filter(Boolean)
    for (const article of externMaterialIssueForm.articles) {
        article.availableStockRequestIsLoading = true
        article.availableStock = null
        article.overbooked = false
    }

    try {
        const response = await axios.post(
            route('inventory.articles.available-stock.batch'),
            {
                article_ids: ids,
                start_date: externMaterialIssueForm.issue_date,
                end_date: externMaterialIssueForm.return_date,
            }
        )

        const resultMap = response.data.data

        for (const article of externMaterialIssueForm.articles) {
            const stock = resultMap[article.id]

            article.availableStockRequestIsLoading = false
            article.availableStock = stock

            if (article.quantity && stock && stock.available < article.quantity) {
                article.overbooked = true
            }
        }

    } catch (error) {
        console.error('Fehler bei Verfügbarkeitsabfrage (Batch):', error)
        for (const article of externMaterialIssueForm.articles) {
            article.availableStockRequestIsLoading = false
            article.availableStock = null
            article.overbooked = false
        }
    }
}

const upload = (event) => {
    const files = event.target.files
    if (files.length > 0) {
        for (let i = 0; i < files.length; i++) {
            externMaterialIssueForm.files.push(files[i])
        }
    }
}

const removeFile = (id) => {
    router.delete(route('extern-issue-of-material.file.delete', id), {
        onSuccess: () => {
            externMaterialIssueForm.files = externMaterialIssueForm.files.filter(file => file.id !== id)
        }
    })
}


watch(
    () => [externMaterialIssueForm.issue_date, externMaterialIssueForm.return_date],
    debounce(() => {
        checkAvailableStock()
    }, 300)
)

onMounted(() => {
    if(props.externMaterialIssue.articles.length > 0){
        checkAvailableStock()
    }
})
</script>

<style scoped>

</style>