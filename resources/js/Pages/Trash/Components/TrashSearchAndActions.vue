<template>
    <div class="flex w-full justify-end items-center -mt-14 gap-x-3">
        <div class="flex items-center w-64">
            <input type="text"
                   :placeholder="$t('Search')"
                   v-model="search"
                   class="h-10 text-base/5 font-semibold text-text border border-border rounded-lg placeholder:text-sm/5 font-bold text-text-subtle placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-text-subtle focus:border-1 w-full border-border"/>
            <IconX v-if="search" class="ml-2 cursor-pointer h-5 w-5" @click="clearSearch()"/>
        </div>
        <button v-if="total > 0" @click="$emit('delete-all')"
                class="cursor-pointer text-danger hover:text-danger">
            <IconTrash class="h-5 w-5" aria-hidden="true"/>
        </button>
    </div>
</template>

<script setup>
import {IconTrash, IconX} from "@tabler/icons-vue";
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash.debounce'

const props = defineProps({
    // Name of the Inertia prop holding the paginator. Used for partial reloads.
    propertyName: { type: String, required: true },
    // Total number of items across all pages (paginator.total).
    total: { type: Number, default: 0 },
})

defineEmits(['delete-all'])

// Initialise from the URL so the input survives full page loads / shared links.
const search = ref(new URLSearchParams(window.location.search).get('search') ?? '')

const reload = debounce(() => {
    router.reload({
        only: [props.propertyName],
        // empty search removes the param; always reset to page 1 on a new query
        data: { search: search.value || undefined, page: 1 },
        preserveScroll: true,
        preserveState: true,
    })
}, 400)

watch(search, () => reload())

const clearSearch = () => {
    search.value = ''
}
</script>
