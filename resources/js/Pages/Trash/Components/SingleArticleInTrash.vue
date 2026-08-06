<template>
    <div class="w-full">
        <div class="flex items-center gap-x-3 justify-between w-full">
            <p class="font-lexend font-semibold text-text">{{ article.name }}</p>
            <p class="rounded-md px-1.5 py-0.5 text-xs font-medium whitespace-nowrap bg-accent-50 border border-accent-200 text-accent-600">{{ article.is_detailed_quantity ? $t('Total quantity') : $t('Quantity') }}: {{ article.quantity }}</p>
        </div>

        <div class="text-xs font-lexend font-extralight text-text-subtle mt-1">
            {{ $t('This article was in the category') }}: {{ article.category.name }}
        </div>

        <div class="text-xs font-lexend font-extralight text-text-subtle" v-if="article.is_detailed_quantity && article.detailed_article_quantities.length > 0">
            {{ $t('This article has detailed quantities') }}:
            <div class="font-bold">
                {{ article.detailed_article_quantities.map((detailedArticle) => {
                return detailedArticle.name + ' (' + $t('Quantity') + ': ' + detailedArticle.quantity + ')'
            }).join(', ') }}
            </div>

        </div>

        <div class="text-xs font-semibold text-text font-lexend w-full mt-2">
            <div class="text-danger">{{ $t('deleted at') }}: {{ article.deleted_at }}</div>
        </div>

    </div>
    <div class="flex flex-none items-center gap-x-2">
        <button @click="restoreArticle" class="flex items-center gap-x-1 rounded-md bg-success-surface border border-success-border px-2.5 py-1.5 text-xs font-semibold text-success hover:bg-success-surface transition">
            <IconRefresh class="h-4 w-4" />
            {{ $t('Restore') }}
        </button>
        <button @click="showConfirmDeleteModal = true" class="flex items-center gap-x-1 rounded-md bg-danger-surface border border-danger-border px-2.5 py-1.5 text-xs font-semibold text-danger hover:bg-danger-surface transition">
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
