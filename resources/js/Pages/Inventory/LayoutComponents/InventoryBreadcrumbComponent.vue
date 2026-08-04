<template>
    <!-- Nur innerhalb einer Kategorie sichtbar; der "Alle Artikel"-Einstieg
         steckt bereits in der Sidebar. -->
    <nav v-if="currentCategory?.id" class="flex font-lexend" aria-label="Breadcrumb">
        <ol role="list" class="flex items-center space-x-2">
            <li>
                <Link preserve-scroll :href="route('inventory.category.show', { inventoryCategory: currentCategory?.id, ...linkQuery })" class="text-sm font-medium text-text-subtle hover:text-text-muted first-letter:capitalize">{{ currentCategory.name }}</Link>
            </li>
            <li v-if="currentSubCategory?.id">
                <div class="flex items-center">
                    <component :is="IconPointFilled" class="size-4 shrink-0 text-text-subtle" aria-hidden="true" />
                    <Link  preserve-scroll :href="route('inventory.sub.category.show', { inventoryCategory: currentCategory.id, inventorySubCategory: currentSubCategory.id, ...linkQuery })" class="ml-2 text-sm font-medium text-text-subtle hover:text-text-muted first-letter:capitalize">
                        {{ currentSubCategory.name }}
                    </Link>
                </div>
            </li>
        </ol>
    </nav>
</template>

<script setup>

import {Link} from "@inertiajs/vue3";
import {IconPointFilled} from "@tabler/icons-vue";

const props = defineProps({
    currentCategory: {
        type: Object,
        required: false
    },
    currentSubCategory: {
        type: Object,
        required: false
    },
    /** URL-gebundene Filter (Status/Suche), die beim Navigieren erhalten bleiben. */
    linkQuery: {
        type: Object,
        required: false,
        default: () => ({})
    }
})

</script>

<style scoped>

</style>
