<template>
    <div>
        <Head>
            <link v-if="page.props.small_logo" rel="icon" type="image/png" :href="page.props.small_logo" />
            <title>{{ title }} - {{ page.props.page_title }}</title>
        </Head>

        <div class="grid grid-cols-1 lg:grid-cols-2 min-h-screen">
            <section class="flex items-center justify-center py-10">
                <div class="w-full max-w-md px-6">
                    <div class="flex items-center gap-4 mb-8">
                        <img v-if="page.props.big_logo" :src="page.props.big_logo" class="h-10 w-auto" alt="Artwork Logo" />
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                        <div class="p-6">
                            <slot />
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-zinc-600">
                        <template v-if="page.props.impressumLink">
                            <a :href="page.props.impressumLink" target="_blank" class="hover:text-blue-700 hover:underline">
                                {{ $t('Imprint') }}
                            </a>
                            <span class="text-zinc-300">|</span>
                        </template>

                        <template v-if="page.props.privacyLink">
                            <a :href="page.props.privacyLink" target="_blank" class="hover:text-blue-700 hover:underline">
                                {{ $t('Privacy Policy') }}
                            </a>
                        </template>
                    </div>
                </div>
            </section>

            <section class="relative hidden lg:flex">
                <img
                    v-if="page.props.banner"
                    class="absolute inset-0 h-full w-full object-cover"
                    :src="page.props.banner"
                    alt="Banner"
                />
            </section>
        </div>
    </div>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3'

defineProps({
    title: { type: String, default: '' },
})

const page = usePage()
</script>
