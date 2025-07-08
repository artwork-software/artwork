<template>
    <form @submit.prevent="submit" class="mx-4">
        <div class="grid gird-cols-1 md:grid-cols-2 gap-x-4 mb-4">
            <div>
                <div class="grid gird-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-full">
                        <BaseInput id="name" v-model="internMaterialIssue.name" :label="$t('Name')" />
                    </div>

                    <div class="col-span-full">
                        <ProjectSearch @project-selected="addProject" v-if="!selectedProject" :label="$t('Project assignment (optional)')"/>

                        <div class="mt-2" v-if="selectedProject">
                            <span class="text-sm font-bold">{{ $t('Selected project') }}:</span>
                            <div class="flex items-start justify-between">
                                <div class="">
                                    <span class="text-sm text-secondary font-bold">{{ selectedProject?.name }}</span>
                                </div>
                                <div>
                                    <button type="button" class="text-blue-500 underline text-xs font-lexend" @click="selectedProject = null">{{ $t('Remove assignment') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-full">
                        <RoomSearch @room-selected="addRoom" v-if="!selectedRoom" :label="$t('Room allocation (optional)')"/>

                        <div class="mt-2" v-if="selectedRoom">
                            <span class="text-sm font-bold">{{ $t('Selected Room') }}:</span>
                            <div class="flex items-start justify-between">
                                <div class="">
                                    <span class="text-sm text-secondary font-bold">{{ selectedRoom?.name }}</span>
                                </div>
                                <div>
                                    <button type="button" class="text-blue-500 underline text-xs font-lexend" @click="selectedRoom = null">{{ $t('Remove assignment') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-1">
                        <BaseInput id="start_date" v-model="internMaterialIssue.start_date" :label="$t('Start date')" type="date" />
                    </div>
                    <div class="col-span-1">
                        <BaseInput id="start_time" v-model="internMaterialIssue.start_time" :label="$t('Start time')" type="time" />
                    </div>

                    <div class="col-span-1">
                        <BaseInput id="end_date" v-model="internMaterialIssue.end_date" :label="$t('End date')" type="date" />
                    </div>
                    <div class="col-span-1">
                        <BaseInput id="end_time" v-model="internMaterialIssue.end_time" :label="$t('End time')" type="time" />
                    </div>

                    <div class="col-span-full">
                        <BaseTextarea id="notes" v-model="internMaterialIssue.notes" :label="$t('Notes')" />
                    </div>

                    <div class="col-span-full">
                        <UserSearch @user-selected="addResponsibleUser"/>

                        <div class="mt-4" v-if="selectedResponsibleUsers.length > 0">
                            <div class="text-sm font-bold mb-3">{{ $t('Selected responsible users') }}:</div>
                            <div  class="flex items-center gap-4 mt-3">
                                <div v-for="(user, index) in selectedResponsibleUsers" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-gray-100">
                                    <div class="flex items-center">
                                        <div>
                                            <img class="inline-block size-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                                        </div>
                                        <div class="mx-2">
                                            <p class="xsDark group-hover:text-gray-900">{{ user?.full_name ?? user.name }}</p>
                                        </div>
                                        <div class="flex items-center">
                                            <button type="button" @click="removeUserFromIssue(index)">
                                                <XIcon class="h-4 w-4 text-gray-400 hover:text-error" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-full">
                        <button @click="$refs.internMaterialIssueFiles.click()" type="button" class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-12 text-center hover:border-gray-400 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden">
                            <component is="IconFile" class="mx-auto size-12 text-gray-400" stroke-width="1" />
                            <span class="mt-2 block text-sm font-semibold text-gray-900">{{ $t('File storage') }}</span>
                            <input
                                @change="upload"
                                class="hidden"
                                ref="internMaterialIssueFiles"
                                id="file"
                                type="file"
                                multiple
                            />
                        </button>

                        <div class="mt-4">
                            <div class="">
                                <div class="divide-y divide-gray-200 divide-dashed">
                                    <div class="py-2" v-for="(file, index) in issueOfMaterial?.files" :key="index">
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
                                    <div class="py-2" v-for="(file, index) in internMaterialIssue.files" :key="index">
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
                                                    <button type="button" class="text-xs text-red-500" @click="internMaterialIssue.files.splice(index, 1)">
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
                    <ArticleSearch @article-selected="addArticleToIssue" id="articleSearchInModal" class="w-full"/>
                    <button type="button" @click="showArticleFilterModal = true" class="p-3 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <ToolTipComponent icon="IconListSearch" :tooltip-text="$t('Search for articles')" icon-size="size-7" tooltip-width="w-fit whitespace-nowrap" position="top" />
                    </button>
                    <button type="button" @click="showSelectMaterialSetModal = true" class="p-3 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ">
                        <ToolTipComponent icon="IconParentheses" :tooltip-text="$t('Select material sets')" icon-size="size-7" tooltip-width="w-fit whitespace-nowrap" position="top" />
                    </button>
                </div>
                <div class="mt-4">
                    <div class="bg-backgroundGray px-4 py-3 rounded-lg border border-gray-200 max-h-96 overflow-scroll">
                        <div class="divide-y divide-gray-200 divide-dashed" v-if="internMaterialIssue.articles.length > 0">
                            <div class="flex items-center gap-x-4 justify-between py-2 border-dashed" v-for="(article, index) in internMaterialIssue.articles" :key="index">
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
                            <div class="py-2" v-for="(article, index) in internMaterialIssue.special_items" :key="index">
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

                    <!-- checkbox for special_items_done -->
                    <div class="mt-4">
                        <label class="flex items-center gap-x-2">
                            <input type="checkbox" v-model="internMaterialIssue.special_items_done" class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                            <span class="text-sm">{{ $t('Special items done') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-center w-full">
            <FormButton
                :text="issueOfMaterial?.id ? $t('Update') : $t('Save')"
                :disabled="internMaterialIssue.processing || !internMaterialIssue.start_date || !internMaterialIssue.end_date || !internMaterialIssue.name"
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
    issueOfMaterial: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            name: '',
            project_id: null,
            start_date: '',
            start_time: '00:00',
            end_date: '',
            end_time: '23:59',
            room_id: null,
            notes: '',
            responsible_user_ids: [],
            special_items_done: false,
            files: [],
            articles: [],
            special_items: [],
            responsible_users: [],
        })
    },
})


const internMaterialIssue = useForm({
    id: props.issueOfMaterial?.id || null,
    name: props.issueOfMaterial?.name || '',
    project_id: props.issueOfMaterial?.project_id || null,
    start_date: props.issueOfMaterial?.start_date || '',
    start_time: props.issueOfMaterial?.start_time || '00:00',
    end_date: props.issueOfMaterial?.end_date || '',
    end_time: props.issueOfMaterial?.end_time || '23:59',
    room_id: props.issueOfMaterial?.room_id || null,
    notes: props.issueOfMaterial?.notes || '',
    responsible_user_ids: props.issueOfMaterial?.responsible_user_ids || [],
    special_items_done: props.issueOfMaterial?.special_items_done || false,
    files: [], // file[] input (no initial value)
    articles: props.issueOfMaterial?.articles || [], // [{ id, quantity }]
    special_items: props.issueOfMaterial?.special_items || [] // [{...}]
})

const selectedProject = ref(props.issueOfMaterial?.project ?? null)
const selectedRoom = ref(props.issueOfMaterial?.room || null)
const selectedResponsibleUsers = ref(props.issueOfMaterial?.responsible_users || [])
const showArticleFilterModal = ref(false)
const showSelectMaterialSetModal = ref(false)

const addProject = (project) => {
    selectedProject.value = project
}

const addRoom = (room) => {
    selectedRoom.value = room
}

const addResponsibleUser = (user) => {
    // Check if the user is already in the array
    const userExists = selectedResponsibleUsers.value.find(u => u.id === user.id)
    if (!userExists) {
        selectedResponsibleUsers.value.push(user)
    }
}

const removeUserFromIssue = (index) => {
    selectedResponsibleUsers.value.splice(index, 1)
}

const addSpecialItem = () => {
    internMaterialIssue.special_items.push({
        id: null,
        name: '',
        quantity: 1
    })
}

const addMaterialSetToIssue = (materialSet) => {
    // check if any article from the material set is already in the issue
    // the article details are in materialSet.items.article
    const existingArticleIds = internMaterialIssue.articles.map(a => a.id)
    const newArticles = materialSet.items.filter(item => !existingArticleIds.includes(item.article.id)).map(item => ({
        id: item.article.id,
        name: item.article.name,
        description: item.article.description,
        quantity: item.quantity || 1, // Default quantity to 1 if not specified
        availableStock: 0,
        availableStockRequestIsLoading: true,
    }))

    // add new articles to the issue
    internMaterialIssue.articles.push(...newArticles)

    // after add check the available stock
    checkAvailableStock()
}

const removeSpecialArticle = (index) => {
    internMaterialIssue.special_items.splice(index, 1)
}

const addArticleToIssue = (article) => {
    // Check if the article is already in the array
    const articleExists = internMaterialIssue.articles.find(a => a.id === article.id)
    if (!articleExists) {
        internMaterialIssue.articles.push({
            id: article.id,
            name: article.name,
            description: article.description,
            quantity: 1,
            availableStock: 0,
            availableStockRequestIsLoading: true,
        })
    }

    // after add check the available stock
    checkAvailableStock()
}

const removeArticle = (index) => {
    internMaterialIssue.articles.splice(index, 1)
}
const emits = defineEmits(['close'])

const submit = () => {

    if(selectedProject.value){
        internMaterialIssue.project_id = selectedProject.value.id
    } else {
        internMaterialIssue.project_id = null
    }

    if(selectedRoom.value){
        internMaterialIssue.room_id = selectedRoom.value.id
    } else {
        internMaterialIssue.room_id = null
    }

    if(selectedResponsibleUsers.value.length > 0){
        internMaterialIssue.responsible_user_ids = selectedResponsibleUsers.value.map(user => user.id)
    } else {
        internMaterialIssue.responsible_user_ids = []
    }

    if(props.issueOfMaterial?.id){
        internMaterialIssue.patch(route('issue-of-material.update', props.issueOfMaterial.id), {
            onSuccess: () => {
                emits('close')
            }
        })
    } else {
        internMaterialIssue.post(route('issue-of-material.store'), {
            onSuccess: () => {
                emits('close')
            }
        })
    }
}


const checkAvailableStock = async () => {
    if (!internMaterialIssue.start_date || !internMaterialIssue.end_date || internMaterialIssue.articles.length === 0) {
        return
    }

    const ids = internMaterialIssue.articles.map(a => a.id).filter(Boolean)

    // Set loading für alle
    for (const article of internMaterialIssue.articles) {
        article.availableStockRequestIsLoading = true
        article.availableStock = null
        article.overbooked = false
    }

    try {
        const response = await axios.post(
            route('inventory.articles.available-stock.batch'),
            {
                article_ids: ids,
                start_date: internMaterialIssue.start_date,
                end_date: internMaterialIssue.end_date,
            }
        )

        const resultMap = response.data.data

        for (const article of internMaterialIssue.articles) {
            const stock = resultMap[article.id]

            article.availableStockRequestIsLoading = false
            article.availableStock = stock

            if (article.quantity && stock && stock.available < article.quantity) {
                article.overbooked = true
            }
        }

    } catch (error) {
        console.error('Fehler bei Verfügbarkeitsabfrage (Batch):', error)
        for (const article of internMaterialIssue.articles) {
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
            internMaterialIssue.files.push(files[i])
        }
    }
}

const removeFile = (id) => {
    router.delete(route('issue-of-material.file.delete', id), {
        onSuccess: () => {
            internMaterialIssue.files = internMaterialIssue.files.filter(file => file.id !== id)
        }
    })
}


watch(
    () => [internMaterialIssue.start_date, internMaterialIssue.end_date],
    debounce(() => {
        checkAvailableStock()
    }, 300)
)

onMounted(() => {
    if(props.issueOfMaterial?.articles?.length > 0){
        checkAvailableStock()
    }
})
</script>

<style scoped>

</style>