<template>
    <Head>
        <link v-if="page.props.small_logo" rel="icon" type="image/png" :href="page.props.small_logo" />
        <title>{{ title }} - {{ page.props.page_title }}</title>
    </Head>
    <div class="artwork relative min-h-screen bg-surface-sunken">
        <ExternalSubMenu />

        <main class="lg:pl-72 pb-20">
            <div class="artwork relative" id="main-content-wrapper">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import ExternalSubMenu from '@/Pages/ExternalAccess/Layouts/ExternalSubMenu.vue'

const { locale } = useI18n()
const page = usePage()

defineProps({
    title: { type: String, default: 'Dashboard' },
})

onMounted(() => {
    if (page.props.locale) {
        document.documentElement.lang = page.props.locale
        locale.value = page.props.locale
    }
})
</script>
