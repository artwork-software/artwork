<template>
    <div class="w-full">
        <div class="flex items-center gap-x-3 justify-between w-full">
            <p class="font-lexend font-semibold text-gray-900">{{ article.name }}</p>
            <p class="rounded-md px-1.5 py-0.5 text-xs font-medium whitespace-nowrap bg-blue-50 border border-blue-100 text-blue-500">{{ article.is_detailed_quantity ? $t('Total quantity') : $t('Quantity') }}: {{ article.quantity }}</p>
        </div>

        <div class="text-xs font-lexend font-extralight text-gray-500 mt-1">
            {{ $t('This article was in the category') }}: {{ article.category.name }}
        </div>

        <div class="text-xs font-lexend font-extralight text-gray-500" v-if="article.is_detailed_quantity && article.detailed_article_quantities.length > 0">
            {{ $t('This article has detailed quantities') }}:
            <div class="font-bold">
                {{ article.detailed_article_quantities.map((detailedArticle) => {
                return detailedArticle.name + ' (' + $t('Quantity') + ': ' + detailedArticle.quantity + ')'
            }).join(', ') }}
            </div>

        </div>

        <div class="text-xs font-semibold text-gray-900 font-lexend w-full mt-2">
            <div class="text-red-500">{{ $t('deleted at') }}: {{ article.deleted_at }}</div>
        </div>

    </div>
    <div class="flex flex-none items-center gap-x-2">
        <button @click="restoreArticle" class="flex items-center gap-x-1 rounded-md bg-green-50 border border-green-200 px-2.5 py-1.5 text-xs font-semibold text-green-700 hover:bg-green-100 transition">
            <IconRefresh class="h-4 w-4" />
            {{ $t('Restore') }}
        </button>
        <button @click="showConfirmDeleteModal = true" class="flex items-center gap-x-1 rounded-md bg-red-50 border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition">
            <IconTrash class="h-4 w-4" />
            {{ $t('Delete permanently') }}
        </button>
    </div>

    <ConfirmDeleteModal
        :title="$t('Delete article')"
        :description="$t('Are you sure you want to delete this article permanently?')"
        v-if="showConfirmDeleteModal"
        @delete="forceDeleteArticle"
        @closed="showConfirmDeleteModal = false"
        />
</template>

<script setup>

import {router} from "@inertiajs/vue3";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {ref} from "vue";
import {IconRefresh, IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    article: {
        type: Object,
        required: true
    }
})


const showConfirmDeleteModal = ref(false)

const restoreArticle = () => {
    router.patch(route('articles.restore', {inventoryArticle: props.article.id}), {
        preserveState: true,
        preserveScroll: true,
    })
}

const forceDeleteArticle = () => {
    router.delete(route('articles.forceDelete', {inventoryArticle: props.article.id}), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showConfirmDeleteModal.value = false
        }
    })
}
</script>

<style scoped>

</style>
