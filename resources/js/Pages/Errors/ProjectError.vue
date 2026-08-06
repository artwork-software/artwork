<script setup lang="ts">
import { computed } from "vue"
import { Head, Link, router } from "@inertiajs/vue3"
import { IconAlertTriangle, IconTrash, IconArrowLeft, IconHome2, IconFolder } from "@tabler/icons-vue"

const props = defineProps<{
    status: number
    title: string
    message: string
    projectsIndexHref?: string
    homeHref?: string
}>()

const isGone = computed(() => Number(props.status) === 410)

function goBack() {
    if (window.history.length > 1) {
        window.history.back()
        return
    }
    if (props.projectsIndexHref) {
        router.visit(props.projectsIndexHref)
        return
    }
    router.visit(props.homeHref ?? "/")
}
</script>

<template>
    <div class="min-h-screen bg-surface-sunken text-text">
        <Head :title="$t(title)" />
        <div class="mx-auto flex min-h-screen max-w-3xl items-center px-4 py-10">
            <div class="w-full rounded-2xl border border-border-subtle bg-white p-6 shadow-sm sm:p-8">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-border-subtle bg-surface-sunken">
                        <component :is="isGone ? IconTrash : IconAlertTriangle" class="h-6 w-6" />
                    </div>

                    <div class="min-w-0">
                        <div class="text-xs font-medium uppercase tracking-wide text-text-subtle">
                            {{ $t('Error') }} {{ status }}
                        </div>
                        <h1 class="mt-1 text-lg font-semibold tracking-tight text-text">
                            {{ $t(title) }}
                        </h1>
                        <p class="mt-2 text-sm leading-6 text-text-muted">
                            {{ $t(message) }}
                        </p>

                        <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <button type="button" @click="goBack" class="inline-flex items-center justify-center gap-2 rounded-xl border border-border-subtle bg-white px-3.5 py-2 text-sm font-medium text-text shadow-sm hover:bg-surface-sunken">
                                <IconArrowLeft class="h-4 w-4" />
                                {{ $t('Back') }}
                            </button>

                            <Link v-if="projectsIndexHref" :href="projectsIndexHref" class="inline-flex items-center justify-center gap-2 rounded-xl border border-border-subtle bg-white px-3.5 py-2 text-sm font-medium text-text shadow-sm hover:bg-surface-sunken">
                                <IconFolder class="h-4 w-4" />
                                {{ $t('Projects') }}
                            </Link>

                            <Link v-if="homeHref" :href="homeHref" class="inline-flex items-center justify-center gap-2 rounded-xl border border-border-subtle bg-white px-3.5 py-2 text-sm font-medium text-text shadow-sm hover:bg-surface-sunken">
                                <IconHome2 class="h-4 w-4" />
                                {{ $t('Dashboard')}}
                            </Link>
                        </div>

                        <div class="mt-6 rounded-xl border border-border-subtle bg-surface-sunken p-3 text-xs text-text-muted">
                            <span class="font-medium">{{ $t('Note') }}:</span>
                            {{ $t('If you think this is a mistake, please check the link or contact the admin.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
