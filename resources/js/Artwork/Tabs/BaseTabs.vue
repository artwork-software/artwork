<template>
    <div class="w-full my-4">
        <div class="overflow-x-auto">
            <nav class="flex gap-2">
                <template v-for="tab in tabs" :key="tab.name">
                    <Link
                        v-if="tab.permission"
                        :href="tab.href"
                        :aria-current="tab.current ? 'page' : undefined"
                        :class="[
              tab.current
                ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20'
                : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50',
              'inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium'
            ]"
                    >
                        <PropertyIcon v-if="tab.icon" :name="tab.icon" class="size-4" />
                        {{ $t(tab.name) }}
                        <span
                            v-if="tab.count"
                            :class="[
                            tab.current ? 'bg-blue-100 text-blue-700' : 'bg-zinc-100 text-zinc-700',
                            'ml-2 hidden rounded-full px-2.5 py-0.5 text-xs font-medium md:inline-block'
                          ]"
                        >{{ tab.count }}</span
                        >
                    </Link>
                </template>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

defineProps({
    tabs: {
        type: Array,
        required: true,
    },
})
</script>
