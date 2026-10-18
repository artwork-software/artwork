<template>
    <header class="sticky top-0 z-40 lg:hidden">
        <div class="flex h-14 items-center justify-between gap-3 border-b border-border-subtle bg-white px-4">
            <Link :href="route('external.dashboard')" class="flex min-w-0 items-center">
                <img v-if="page.props.big_logo" class="h-7 w-auto" :src="page.props.big_logo" alt="Logo" />
                <span v-else class="truncate text-sm font-semibold text-text">{{ page.props.page_title }}</span>
            </Link>

            <!-- Wenige Einträge: direkt als Icons, sonst Menü -->
            <nav v-if="!usesMenu" class="flex shrink-0 items-center gap-1" :aria-label="$t('Navigation')">
                <Link
                    v-for="entry in entries"
                    :key="entry.key"
                    :href="entry.href"
                    :aria-label="entry.label"
                    :title="entry.label"
                    :aria-current="entry.current ? 'page' : undefined"
                    :class="iconButtonClasses(entry.current)"
                >
                    <component :is="entry.icon" class="size-6" />
                </Link>
                <button
                    type="button"
                    :aria-label="$t('Logout')"
                    :title="$t('Logout')"
                    :class="iconButtonClasses(false)"
                    @click="logout"
                >
                    <IconLogout class="size-6" />
                </button>
            </nav>
            <button
                v-else
                type="button"
                :aria-label="menuOpen ? $t('Close menu') : $t('Open menu')"
                :aria-expanded="String(menuOpen)"
                aria-controls="external-mobile-menu"
                :class="iconButtonClasses(menuOpen)"
                @click="menuOpen = !menuOpen"
            >
                <IconX v-if="menuOpen" class="size-6" />
                <IconMenu2 v-else class="size-6" />
            </button>
        </div>

        <nav
            v-if="usesMenu && menuOpen"
            id="external-mobile-menu"
            class="fixed inset-x-0 bottom-0 top-14 flex flex-col overflow-y-auto bg-white px-4 pb-6 pt-2"
            :aria-label="$t('Navigation')"
        >
            <ul role="list" class="space-y-1">
                <li v-for="entry in mainEntries" :key="entry.key">
                    <Link :href="entry.href" :class="menuItemClasses(entry.current)" @click="menuOpen = false">
                        <component :is="entry.icon" class="size-6 shrink-0" />
                        <span>{{ entry.label }}</span>
                    </Link>
                </li>
            </ul>

            <div v-if="groupedScopes.length" class="mt-6">
                <div class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-text-subtle">
                    {{ $t('Shared with you') }}
                </div>
                <div v-for="group in groupedScopes" :key="group.project.id" class="mb-4">
                    <div class="px-3 py-1 text-sm font-medium text-text">{{ group.project.name }}</div>
                    <ul role="list" class="space-y-1">
                        <li v-for="scope in group.scopes" :key="scope.id">
                            <Link :href="scopeHref(scope)" :class="menuItemClasses(isCurrentScope(scope))" @click="menuOpen = false">
                                <IconForms class="size-6 shrink-0" />
                                <span class="min-w-0 flex-1 truncate">{{ scope.tab.name }}</span>
                                <span v-if="scope.access_type === 'read'" class="shrink-0 text-xs font-normal text-text-subtle">
                                    {{ $t('read only') }}
                                </span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-auto pt-6">
                <div v-if="page.props.crm_access_active && page.props.crm_access_expires_at" class="mb-2 px-3 text-xs text-text-subtle">
                    {{ $t('CRM access valid until') }}: {{ formatExternalDate(page.props.crm_access_expires_at) }}
                </div>
                <button
                    type="button"
                    class="flex w-full items-center justify-center gap-x-2 rounded-md bg-surface-sunken px-3 py-3 text-base font-semibold text-text hover:bg-surface-sunken/70"
                    @click="logout"
                >
                    <IconLogout class="size-5 shrink-0" />
                    {{ $t('Logout') }}
                </button>
            </div>
        </nav>
    </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import { IconForms, IconHome, IconLogout, IconMenu2, IconUser, IconX } from '@tabler/icons-vue'
import { useTranslation } from '@/Composeables/Translation.js'
import { formatExternalDate, useExternalNavigation } from '@/Pages/ExternalAccess/Layouts/useExternalNavigation.js'

// Inklusive Abmelden passen höchstens so viele Einträge als Icons in die Kopfleiste.
const MAX_INLINE_ENTRIES = 3

const $t = useTranslation()
const { page, scopes, groupedScopes, scopeHref, isCurrentScope, logout } = useExternalNavigation()

const menuOpen = ref(false)

const mainEntries = computed(() => {
    const result = [{
        key: 'dashboard',
        label: $t('Overview'),
        icon: IconHome,
        href: route('external.dashboard'),
        current: route().current('external.dashboard'),
    }]
    if (page.props.crm_access_active) {
        result.push({
            key: 'crm',
            label: $t('My data'),
            icon: IconUser,
            href: route('external.crm.show'),
            current: route().current('external.crm.*'),
        })
    }
    return result
})

const entries = computed(() => [
    ...mainEntries.value,
    ...scopes.value.map((scope) => ({
        key: `scope-${scope.id}`,
        label: `${scope.project.name} — ${scope.tab.name}`,
        icon: IconForms,
        href: scopeHref(scope),
        current: isCurrentScope(scope),
    })),
])

const usesMenu = computed(() => entries.value.length + 1 > MAX_INLINE_ENTRIES)

function iconButtonClasses(isCurrent) {
    return [
        'flex size-11 items-center justify-center rounded-md',
        isCurrent ? 'bg-surface-sunken text-text' : 'text-text-muted hover:bg-surface-sunken hover:text-text',
    ]
}

function menuItemClasses(isCurrent) {
    return [
        'flex items-center gap-x-3 rounded-md px-3 py-3 text-base font-semibold',
        isCurrent ? 'bg-surface-sunken text-text' : 'text-text-muted hover:bg-surface-sunken hover:text-text',
    ]
}

// Seite dahinter soll bei offenem Menü nicht mitscrollen
watch(menuOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
})

function closeOnEscape(event) {
    if (event.key === 'Escape') menuOpen.value = false
}

onMounted(() => document.addEventListener('keydown', closeOnEscape))
onBeforeUnmount(() => {
    document.removeEventListener('keydown', closeOnEscape)
    document.body.style.overflow = ''
})
</script>
