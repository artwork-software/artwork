<!-- resources/js/Components/Modals/AddEditTabModal.vue -->
<script>
import axios from "axios"
import { useForm } from "@inertiajs/vue3"

import IconLib from "@/Mixins/IconLib.vue"
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue"
import BaseInput from "@/Artwork/Inputs/BaseInput.vue"
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue"

export default {
    name: "AddEditTabModal",
    mixins: [IconLib],
    components: {
        ArtworkBaseModal,
        BaseInput,
        BaseUIButton,
    },
    emits: ["close"],
    props: ["tabToEdit"],

    data() {
        return {
            userAndTeamsQuery: "",
            userAndTeamsSearchResult: {
                users: [],
                departments: [],
            },
            selectedVisibleUsers: this.tabToEdit?.visible_users ?? [],
            selectedVisibleDepartments: this.tabToEdit?.visible_departments ?? [],
            _searchTimer: null,

            tabForm: useForm({
                name: this.tabToEdit ? this.tabToEdit.name : "",
                visible_for_all: this.tabToEdit ? !!this.tabToEdit.visible_for_all : true,
                visible_user_ids: (this.tabToEdit?.visible_users ?? []).map((u) => u.id),
                visible_department_ids: (this.tabToEdit?.visible_departments ?? []).map((d) => d.id),
            }),
        }
    },

    watch: {
        "tabForm.visible_for_all"(v) {
            if (v) {
                this.selectedVisibleUsers = []
                this.selectedVisibleDepartments = []
                this.syncIdsToForm()
                this.resetSearch()
            }
        },
        userAndTeamsQuery() {
            if (this._searchTimer) clearTimeout(this._searchTimer)
            this._searchTimer = setTimeout(() => this.searchUsersAndTeams(), 250)
        },
    },

    methods: {
        closeModal(bool = false) {
            this.$emit("close", bool)
        },

        resetSearch() {
            this.userAndTeamsQuery = ""
            this.userAndTeamsSearchResult.users = []
            this.userAndTeamsSearchResult.departments = []
        },

        async searchUsersAndTeams() {
            const q = this.userAndTeamsQuery?.trim()
            if (!q) {
                this.resetSearch()
                return
            }

            try {
                const { data } = await axios.get(route("users_departments.search"), {
                    params: { query: q },
                })

                this.userAndTeamsSearchResult.users = data.users ?? []
                this.userAndTeamsSearchResult.departments = data.departments ?? []
            } catch (e) {
                this.userAndTeamsSearchResult.users = []
                this.userAndTeamsSearchResult.departments = []
            }
        },

        // provider_name gibt es nicht
        displayUserName(u) {
            const fn = (u?.first_name ?? "").trim()
            const ln = (u?.last_name ?? "").trim()
            const full = `${fn} ${ln}`.trim()
            return full || `User #${u?.id ?? "?"}`
        },

        addVisibleUser(u) {
            if (!u?.id) return
            if (this.selectedVisibleUsers.some((x) => x.id === u.id)) return
            this.selectedVisibleUsers.push(u)
            this.syncIdsToForm()
        },

        removeVisibleUser(id) {
            this.selectedVisibleUsers = this.selectedVisibleUsers.filter((x) => x.id !== id)
            this.syncIdsToForm()
        },

        addVisibleDepartment(d) {
            if (!d?.id) return
            if (this.selectedVisibleDepartments.some((x) => x.id === d.id)) return
            this.selectedVisibleDepartments.push(d)
            this.syncIdsToForm()
        },

        removeVisibleDepartment(id) {
            this.selectedVisibleDepartments = this.selectedVisibleDepartments.filter((x) => x.id !== id)
            this.syncIdsToForm()
        },

        syncIdsToForm() {
            if (this.tabForm.visible_for_all) {
                this.tabForm.visible_user_ids = []
                this.tabForm.visible_department_ids = []
                return
            }

            this.tabForm.visible_user_ids = this.selectedVisibleUsers.map((u) => u.id)
            this.tabForm.visible_department_ids = this.selectedVisibleDepartments.map((d) => d.id)
        },

        saveTab() {
            this.syncIdsToForm()

            if (this.tabToEdit) {
                this.tabForm.patch(route("tab.update", { projectTab: this.tabToEdit.id }), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => this.closeModal(false),
                })
            } else {
                this.tabForm.post(route("tab.store"), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => this.closeModal(false),
                })
            }
        },
    },
}
</script>

<template>
    <ArtworkBaseModal
        :title="tabToEdit ? $t('Edit tab') : $t('Create tab')"
        :description="tabToEdit ? $t('Update the tab name and visibility rules.') : $t('Create a new tab and define who can see it.')"
        @close="closeModal(false)"
    >
        <div class="space-y-6">
            <!-- Name -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white/70 backdrop-blur p-4 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-zinc-900">{{ $t('Tab name') }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500">
                            {{ $t('This name is shown in the project navigation.') }}
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <BaseInput type="text" v-model="tabForm.name" :label="$t('Name')" id="tab_name" />
                    <div v-if="tabForm.errors?.name" class="text-xs text-red-600 mt-1">
                        {{ tabForm.errors.name }}
                    </div>
                </div>
            </div>

            <!-- Visibility -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white/70 backdrop-blur p-4 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-zinc-900">{{ $t('Visibility') }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500">
                            {{ $t('Decide who can see this tab. Restricted tabs cannot be opened via direct URL.') }}
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 select-none">
                        <input
                            type="checkbox"
                            v-model="tabForm.visible_for_all"
                            class="h-4 w-4 rounded border-zinc-300"
                        />
                        <span class="text-sm text-zinc-800">{{ $t('Visible for all') }}</span>
                    </label>
                </div>

                <!-- Restricted -->
                <div v-if="!tabForm.visible_for_all" class="mt-4 space-y-4">
                    <!-- Search -->
                    <div class="rounded-xl border border-zinc-200 bg-white/70 p-3">
                        <BaseInput
                            type="text"
                            v-model="userAndTeamsQuery"
                            :label="$t('Visible for')"
                            id="tab_visible_for"
                            :placeholder="$t('Search users or teams…')"
                        />
                        <div class="text-xs text-zinc-500 mt-2">
                            {{ $t('Type to search users and teams, then click to add them.') }}
                        </div>
                    </div>

                    <!-- Search results -->
                    <div v-if="userAndTeamsQuery.trim()" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Users -->
                        <div class="rounded-xl border border-zinc-200 bg-white/70 overflow-hidden">
                            <div class="px-3 py-2 border-b border-zinc-200/70 bg-zinc-50/60">
                                <div class="text-xs font-medium text-zinc-700 uppercase tracking-wide">
                                    {{ $t('Users') }}
                                </div>
                            </div>

                            <div class="max-h-56 overflow-auto">
                                <button
                                    v-for="u in userAndTeamsSearchResult.users"
                                    :key="'u-' + u.id"
                                    type="button"
                                    class="w-full text-left px-3 py-2 hover:bg-black/5 flex items-center justify-between"
                                    @click="addVisibleUser(u)"
                                >
                                    <span class="text-sm text-zinc-900 truncate">{{ displayUserName(u) }}</span>
                                    <span class="text-xs text-zinc-500">+</span>
                                </button>

                                <div
                                    v-if="!userAndTeamsSearchResult.users.length"
                                    class="px-3 py-3 text-sm text-zinc-500"
                                >
                                    {{ $t('No users found.') }}
                                </div>
                            </div>
                        </div>

                        <!-- Teams -->
                        <div class="rounded-xl border border-zinc-200 bg-white/70 overflow-hidden">
                            <div class="px-3 py-2 border-b border-zinc-200/70 bg-zinc-50/60">
                                <div class="text-xs font-medium text-zinc-700 uppercase tracking-wide">
                                    {{ $t('Teams') }}
                                </div>
                            </div>

                            <div class="max-h-56 overflow-auto">
                                <button
                                    v-for="d in userAndTeamsSearchResult.departments"
                                    :key="'d-' + d.id"
                                    type="button"
                                    class="w-full text-left px-3 py-2 hover:bg-black/5 flex items-center justify-between"
                                    @click="addVisibleDepartment(d)"
                                >
                                    <span class="text-sm text-zinc-900 truncate">{{ d.name }}</span>
                                    <span class="text-xs text-zinc-500">+</span>
                                </button>

                                <div
                                    v-if="!userAndTeamsSearchResult.departments.length"
                                    class="px-3 py-3 text-sm text-zinc-500"
                                >
                                    {{ $t('No teams found.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected -->
                    <div class="rounded-xl border border-zinc-200 bg-white/70 p-3">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs font-medium text-zinc-700 uppercase tracking-wide">
                                    {{ $t('Selected access') }}
                                </div>
                                <div class="mt-0.5 text-xs text-zinc-500">
                                    {{ $t('Only selected users and teams can see this tab.') }}
                                </div>
                            </div>

                            <div class="text-xs text-zinc-500">
                                {{ selectedVisibleUsers.length + selectedVisibleDepartments.length }} {{ $t('items') }}
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="u in selectedVisibleUsers"
                                :key="'su-' + u.id"
                                class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-zinc-200 bg-white/70 text-sm text-zinc-800"
                            >
                                <span class="truncate max-w-[16rem]">{{ displayUserName(u) }}</span>
                                <button
                                    type="button"
                                    class="opacity-60 hover:opacity-100"
                                    @click="removeVisibleUser(u.id)"
                                    :aria-label="$t('Remove')"
                                >
                                    ✕
                                </button>
                            </span>

                            <span
                                v-for="d in selectedVisibleDepartments"
                                :key="'sd-' + d.id"
                                class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-zinc-200 bg-white/70 text-sm text-zinc-800"
                            >
                                <span class="truncate max-w-[16rem]">{{ d.name }}</span>
                                <button
                                    type="button"
                                    class="opacity-60 hover:opacity-100"
                                    @click="removeVisibleDepartment(d.id)"
                                    :aria-label="$t('Remove')"
                                >
                                    ✕
                                </button>
                            </span>

                            <span
                                v-if="!selectedVisibleUsers.length && !selectedVisibleDepartments.length"
                                class="text-sm text-zinc-500"
                            >
                                {{ $t('Nothing selected yet.') }}
                            </span>
                        </div>

                        <div
                            v-if="!selectedVisibleUsers.length && !selectedVisibleDepartments.length"
                            class="mt-3 rounded-lg border border-amber-200 bg-amber-50/70 px-3 py-2 text-xs text-amber-800"
                        >
                            {{ $t('Warning: If nobody is selected, nobody will be able to see this tab.') }}
                        </div>

                        <div v-if="tabForm.errors?.visible_user_ids" class="text-xs text-red-600 mt-2">
                            {{ tabForm.errors.visible_user_ids }}
                        </div>
                        <div v-if="tabForm.errors?.visible_department_ids" class="text-xs text-red-600 mt-1">
                            {{ tabForm.errors.visible_department_ids }}
                        </div>
                    </div>
                </div>

                <!-- Visible for all helper -->
                <div v-else class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/50 px-3 py-2 text-xs text-emerald-800">
                    {{ $t('Everyone with access to the project can see this tab.') }}
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-between items-center">
                <BaseUIButton
                    @click="saveTab"
                    is-add-button
                    :label="tabToEdit ? $t('Save changes') : $t('Create tab')"
                    :disabled="tabForm.processing"
                />

                <BaseUIButton
                    @click="closeModal(false)"
                    is-cancel-button
                    :label="$t('Cancel')"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<style scoped></style>
