<template>
    <div class="flex w-full justify-end items-center -mt-14 gap-x-3">
        <div class="flex items-center w-64">
            <input type="text"
                   :placeholder="$t('Search')"
                   v-model="search"
                   class="h-10 sDark inputMain rounded-lg placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
            <XIcon v-if="search" class="ml-2 cursor-pointer h-5 w-5" @click="clearSearch()"/>
        </div>
        <button v-if="total > 0" @click="$emit('delete-all')"
                class="cursor-pointer text-red-500 hover:text-red-700">
            <TrashIcon class="h-5 w-5" aria-hidden="true"/>
        </button>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { TrashIcon, XIcon } from '@heroicons/vue/outline'
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
