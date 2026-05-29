<template>
    <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:flex-col lg:w-72">
        <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-artwork-navigation-background px-6 pb-4">
            <div class="flex h-16 shrink-0 items-center">
                <img v-if="page.props.big_logo" class="h-8 w-auto" :src="page.props.big_logo" alt="Logo" />
            </div>

            <nav class="flex flex-1 flex-col">
                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                        <Link
                            :href="route('external.crm.show')"
                            :class="navItemClasses(route().current('external.crm.show'))"
                        >
                            <PropertyIcon name="IconUser" class="size-6 shrink-0" />
                            <span>{{ $t('My data') }}</span>
                        </Link>
                    </li>

                    <li v-if="groupedScopes.length > 0">
                        <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-2">
                            {{ $t('Shared with you') }}
                        </div>
                        <ul role="list" class="space-y-3">
                            <li v-for="group in groupedScopes" :key="group.project.id">
                                <div class="text-sm font-medium text-zinc-200 mb-1">
                                    {{ group.project.name }}
                                </div>
                                <ul class="ml-2 space-y-1">
                                    <li v-for="scope in group.scopes" :key="scope.id">
                                        <a
                                            href="#"
                                            :class="navItemClasses(false)"
                                            @click.prevent="onTabClick(scope)"
                                        >
                                            <span>{{ scope.tab.name }}</span>
                                            <span
                                                v-if="scope.access_type === 'read'"
                                                class="ml-auto text-xs text-zinc-400"
                                            >
                                                {{ $t('read only') }}
                                            </span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>

                    <li class="mt-auto">
                        <div v-if="page.props.crm_access_expires_at" class="text-xs text-zinc-400 mb-2">
                            {{ $t('CRM access valid until') }}: {{ formatDate(page.props.crm_access_expires_at) }}
                        </div>
                        <form @submit.prevent="logout">
                            <button
                                type="submit"
                                class="w-full flex items-center justify-center gap-x-2 rounded-md bg-white/5 px-3 py-2 text-sm font-semibold text-zinc-200 hover:bg-white/10"
                            >
                                <PropertyIcon name="IconLogout" class="size-5 shrink-0" />
                                {{ $t('Logout') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

const page = usePage()

const groupedScopes = computed(() => {
    const groups = new Map()
    for (const scope of page.props.accessible_scopes ?? []) {
        const key = scope.project.id
        if (!groups.has(key)) {
            groups.set(key, { project: scope.project, scopes: [] })
        }
        groups.get(key).scopes.push(scope)
    }
    return Array.from(groups.values())
})

function logout() {
    router.post(route('external.logout'))
}

function onTabClick(scope) {
    // Tab-Routen werden im Tab-Sharing-Paket nachgeliefert. Vorerst kein Navigations-Ziel.
    console.info('External tab clicked (route not yet implemented):', scope)
}

function navItemClasses(isCurrent) {
    return [
        'group flex items-center gap-x-3 rounded-md p-2 text-sm font-semibold',
        isCurrent
            ? 'bg-white/10 text-white'
            : 'text-zinc-300 hover:bg-white/5 hover:text-white',
    ]
}

function formatDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString()
}
</script>
