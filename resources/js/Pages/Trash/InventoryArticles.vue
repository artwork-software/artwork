<template>
    <TrashTab
        :paginator="trashedArticles"
        property-name="trashedArticles"
        :force-all-href="route('articles.forceDeleteAll')"
        :restore-href="(article) => route('articles.restore', { inventoryArticle: article.id })"
        :force-href="(article) => route('articles.forceDelete', { inventoryArticle: article.id })"
        :name-of="(article) => article.name"
        :open-href="() => route('inventory.index')"
    >
        <template #default="{ item: article }">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="truncate font-medium text-text">{{ article.name }}</span>
                <span class="rounded-md border border-accent-200 bg-accent-50 px-1.5 py-0.5 text-xs font-medium whitespace-nowrap text-accent-600">
                    {{ article.is_detailed_quantity ? $t('Total quantity') : $t('Quantity') }}: {{ article.quantity }}
                </span>
            </div>
            <div class="mt-0.5 truncate text-sm text-text-muted">
                <span v-if="article.category">{{ $t('Category') }}: {{ article.category.name }}</span>
                <template v-if="article.is_detailed_quantity && article.detailed_article_quantities?.length > 0">
                    <span class="text-text-subtle"> · </span>
                    <span>{{ article.detailed_article_quantities.map((detail) => detail.name + ' (' + detail.quantity + ')').join(', ') }}</span>
                </template>
            </div>
        </template>
    </TrashTab>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import TrashLayout from '@/Layouts/TrashLayout.vue'

export default {
    layout: [AppLayout, TrashLayout],
}
</script>

<script setup>
import TrashTab from '@/Pages/Trash/Components/TrashTab.vue'
defineProps({
    trashedArticles: { type: Object, required: true },
})
</script>
