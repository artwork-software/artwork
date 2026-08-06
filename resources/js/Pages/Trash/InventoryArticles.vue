<template>
    <AppLayout>
        <TrashLayout>

            <TrashSearchAndActions
                property-name="trashedArticles"
                :total="trashedArticles.total"
                @delete-all="showConfirmDeleteAll = true"
            />

            <ul role="list" class="divide-y divide-border-subtle min-w-7xl w-full" v-if="trashedArticles.total > 0">
                <li v-for="article in trashedArticles.data" :key="article.id" class="flex items-center justify-between gap-x-6 py-5 ">
                    <SingleArticleInTrash :article="article" />
                </li>
            </ul>

            <div v-else class="mt-2 min-w-7xl w-full">
                <BaseAlertComponent message="No articles found" use-translation type="error" />
            </div>

            <BasePaginator
                v-if="trashedArticles.total > 0"
                :entities="trashedArticles"
                property-name="trashedArticles"
                class="mt-6"
            />
        </TrashLayout>
    </AppLayout>

    <ConfirmDeleteModal
        v-if="showConfirmDeleteAll"
        :title="$t('Delete all')"
        :description="$t('Are you sure you want to permanently delete all items in the recycle bin for this category?')"
        @closed="showConfirmDeleteAll = false"
        @delete="forceDeleteAll"
    />
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";
import SingleArticleInTrash from "@/Pages/Trash/Components/SingleArticleInTrash.vue";
import TrashSearchAndActions from "@/Pages/Trash/Components/TrashSearchAndActions.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

defineProps({
    trashedArticles: {
        type: Object,
        required: true
    }
})

const showConfirmDeleteAll = ref(false)

const forceDeleteAll = () => {
    router.delete(route('articles.forceDeleteAll'), {
        onSuccess: () => {
            showConfirmDeleteAll.value = false;
        }
    });
}
</script>

<style scoped>

</style>
