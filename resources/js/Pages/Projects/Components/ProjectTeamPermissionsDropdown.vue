<template>
    <div ref="dropdown" class="relative flex-shrink-0">
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-border-subtle bg-white px-3 py-2 text-sm font-medium text-primary transition hover:border-border hover:bg-surface-sunken"
            aria-haspopup="menu"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span v-if="rolesOnly">{{ $t('Project Roles') }}</span>
            <span v-else>{{ $t('Permissions') }} &amp; {{ $t('Project Roles') }}</span>
            <span
                v-if="activeAssignmentCount > 0"
                class="inline-flex min-w-5 items-center justify-center rounded-full bg-artwork-buttons-create/10 px-1.5 py-0.5 text-xs font-semibold text-primary"
            >
                {{ activeAssignmentCount }}
            </span>
            <ChevronDownIcon class="h-4 w-4" aria-hidden="true" />
        </button>

        <div v-show="open" class="fixed inset-0 z-40" @click="open = false" />

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-show="open"
                ref="dropdownContent"
                role="menu"
                class="absolute right-0 z-50 max-w-[calc(100vw-2rem)] rounded-lg bg-white shadow-lg ring-1 ring-black/5"
                :class="[
                    opensUpward ? 'bottom-full mb-2 origin-bottom-right' : 'top-full mt-2 origin-top-right',
                    rolesOnly ? 'w-64' : 'w-96'
                ]"
                @click.stop
            >
                <div class="grid w-full text-left" :class="rolesOnly ? 'grid-cols-1' : 'grid-cols-2'">
                    <div v-if="!rolesOnly" class="p-4 pr-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-text-subtle">
                            {{ $t('Permissions') }}
                        </div>

                        <div class="mt-2 space-y-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-surface-sunken">
                                <input
                                    :checked="user.pivot_can_write"
                                    type="checkbox"
                                    class="h-5 w-5 cursor-pointer rounded border-2 border-border text-success ring-offset-0 focus:ring-0 focus:shadow-none"
                                    @change="updatePermission('pivot_can_write', $event)"
                                />
                                <span class="text-sm text-secondary">{{ $t('Write permission') }}</span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-surface-sunken">
                                <input
                                    :checked="user.pivot_access_budget"
                                    type="checkbox"
                                    class="h-5 w-5 cursor-pointer rounded border-2 border-border text-success ring-offset-0 focus:ring-0 focus:shadow-none"
                                    @change="updatePermission('pivot_access_budget', $event)"
                                />
                                <span class="text-sm text-secondary">{{ $t('Budget access') }}</span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-surface-sunken">
                                <input
                                    :checked="user.pivot_delete_permission"
                                    type="checkbox"
                                    class="h-5 w-5 cursor-pointer rounded border-2 border-border text-success ring-offset-0 focus:ring-0 focus:shadow-none"
                                    @change="updatePermission('pivot_delete_permission', $event)"
                                />
                                <span class="text-sm text-secondary">{{ $t('Permission to delete') }}</span>
                            </label>

                            <label
                                v-if="user.project_management"
                                class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-surface-sunken"
                            >
                                <input
                                    :checked="user.pivot_is_manager"
                                    type="checkbox"
                                    class="h-5 w-5 cursor-pointer rounded border-2 border-border text-success ring-offset-0 focus:ring-0 focus:shadow-none"
                                    @change="updatePermission('pivot_is_manager', $event)"
                                />
                                <span class="text-sm text-secondary">{{ $t('Project management') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="p-4" :class="rolesOnly ? '' : 'border-l border-border-subtle pl-5'">
                        <div class="text-xs font-semibold uppercase tracking-wide text-text-subtle">
                            {{ $t('Project Roles') }}
                        </div>

                        <div v-if="projectRoles.length > 0" class="mt-2 space-y-2">
                            <label
                                v-for="role in projectRoles"
                                :key="`role-${user.id}-${role.id}`"
                                class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-surface-sunken"
                            >
                                <input
                                    :id="`role-${user.id}-${role.id}`"
                                    :name="role.name"
                                    type="checkbox"
                                    class="h-5 w-5 cursor-pointer rounded border-2 border-border text-success ring-offset-0 focus:ring-0 focus:shadow-none"
                                    :checked="user.pivot_roles?.includes(role.id)"
                                    @change="$emit('toggle-role', role)"
                                />
                                <span class="text-sm text-secondary">{{ role.name }}</span>
                            </label>
                        </div>

                        <template v-else>
                            <Link
                                v-if="canManageProjectRoles"
                                class="linkText mt-2 inline-block text-sm font-medium text-primary"
                                :href="route('project-roles.index')"
                            >
                                {{ $t('No project roles created yet') }}
                            </Link>
                            <span v-else class="mt-2 inline-block text-xs text-secondary">
                                {{ $t('No project roles created yet') }}
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import {computed, nextTick, onMounted, onUnmounted, ref, watch} from 'vue'
import {Link} from '@inertiajs/vue3'
import {ChevronDownIcon} from '@heroicons/vue/solid'

const props = defineProps({
    user: {type: Object, required: true},
    projectRoles: {type: Array, required: true},
    canManageProjectRoles: {type: Boolean, default: false},
    rolesOnly: {type: Boolean, default: false},
})

const emit = defineEmits(['update-permission', 'toggle-role'])

const open = ref(false)
const dropdown = ref(null)
const dropdownContent = ref(null)
const opensUpward = ref(false)

const activeAssignmentCount = computed(() => {
    return [
        props.user.pivot_can_write,
        props.user.pivot_access_budget,
        props.user.pivot_delete_permission,
        props.user.pivot_is_manager,
    ].filter(Boolean).length + (props.user.pivot_roles?.length ?? 0)
})

const updatePermission = (permission, event) => {
    emit('update-permission', {
        permission,
        value: event.target.checked,
    })
}

const closeOnEscape = (event) => {
    if (open.value && event.key === 'Escape') {
        open.value = false
    }
}

watch(open, async (isOpen) => {
    if (!isOpen) {
        opensUpward.value = false
        return
    }

    await nextTick()

    const triggerBounds = dropdown.value?.getBoundingClientRect()
    const contentHeight = dropdownContent.value?.offsetHeight ?? 0
    if (!triggerBounds || contentHeight === 0) {
        return
    }

    const spaceBelow = window.innerHeight - triggerBounds.bottom
    opensUpward.value = contentHeight > spaceBelow && triggerBounds.top > spaceBelow
})

onMounted(() => document.addEventListener('keydown', closeOnEscape))
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape))
</script>
