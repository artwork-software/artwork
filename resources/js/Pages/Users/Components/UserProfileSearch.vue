<template>
    <div class="relative w-full max-w-sm" ref="rootEl">
        <BaseInput
            id="userProfileSearch"
            v-model="query"
            :label="$t('Search for users')"
            :show-loading="loading"
            @focusin="onFocus"
            @keydown.down.prevent="moveHighlight(1)"
            @keydown.up.prevent="moveHighlight(-1)"
            @keydown.enter.prevent="selectHighlighted"
            @keydown.esc="closeDropdown"
        />

        <transition
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open && query.length > 0"
                class="absolute z-50 mt-1 w-full max-h-72 overflow-auto rounded-md bg-white
                       shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
            >
                <ul v-if="results.length > 0" class="divide-y divide-border-subtle">
                    <li
                        v-for="(user, index) in results"
                        :key="user.id"
                        @click="goToUser(user)"
                        @mouseenter="highlightedIndex = index"
                        :class="[
                            'flex items-center gap-3 px-4 py-2 cursor-pointer',
                            index === highlightedIndex ? 'bg-surface-sunken' : ''
                        ]"
                    >
                        <img
                            class="h-9 w-9 rounded-full object-cover shrink-0"
                            :src="user.profile_photo_url"
                            alt=""
                        />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-text">
                                {{ user.first_name }} {{ user.last_name }}
                            </p>
                            <p v-if="user.email" class="truncate text-xs text-text-subtle">
                                {{ user.email }}
                            </p>
                        </div>
                    </li>
                </ul>
                <div v-else-if="!loading" class="px-4 py-3 text-sm text-text-subtle">
                    {{ $t('No results found') }}
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import { router } from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import { usePermission } from "@/Composeables/Permission.js";

export default {
    name: "UserProfileSearch",
    components: { BaseInput },
    data() {
        return {
            query: '',
            results: [],
            loading: false,
            open: false,
            highlightedIndex: -1,
            debounceTimer: null,
        };
    },
    watch: {
        query() {
            this.highlightedIndex = -1;
            clearTimeout(this.debounceTimer);

            if (this.query.trim().length === 0) {
                this.results = [];
                this.loading = false;
                return;
            }

            this.open = true;
            this.debounceTimer = setTimeout(this.fetchResults, 300);
        },
    },
    mounted() {
        document.addEventListener('click', this.handleOutsideClick);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleOutsideClick);
        clearTimeout(this.debounceTimer);
    },
    methods: {
        fetchResults() {
            const currentQuery = this.query.trim();
            if (currentQuery.length === 0) {
                return;
            }

            this.loading = true;
            axios.get(route('users.search'), { params: { query: currentQuery } })
                .then((response) => {
                    // Nur reagieren, wenn die Eingabe seither nicht wieder verändert wurde
                    if (this.query.trim() === currentQuery) {
                        this.results = response.data ?? [];
                    }
                })
                .catch(() => {
                    this.results = [];
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        goToUser(user) {
            const target = this.linkForUser(user);
            if (!target) {
                return;
            }
            this.closeDropdown();
            this.query = '';
            this.results = [];
            router.get(target);
        },
        // Sichtregel (Spiegel der Backend-Autorisierung): User-Einsatzplan nur mit
        // Dienstplan-Sichtrechten (sonst Personendaten-Tab); Freelancer-/Dienstleister-
        // Profile zusätzlich mit "can view private user info", ohne beides nicht anwählbar.
        linkForUser(user) {
            const { canViewForeignRoster, canViewExternalWorkerProfile } = usePermission(this.$page.props);
            if (user.type === 'freelancer') {
                return canViewExternalWorkerProfile() ? route('freelancer.show', { freelancer: user.id }) : null;
            }
            if (user.type === 'service_provider') {
                return canViewExternalWorkerProfile() ? route('service_provider.show', { serviceProvider: user.id }) : null;
            }
            if (canViewForeignRoster() || user.id === this.$page.props.auth.user.id) {
                return route('user.edit.shiftplan', { user: user.id });
            }
            return route('user.edit.info', { user: user.id });
        },
        onFocus() {
            if (this.query.trim().length > 0) {
                this.open = true;
            }
        },
        closeDropdown() {
            this.open = false;
            this.highlightedIndex = -1;
        },
        handleOutsideClick(event) {
            if (this.$refs.rootEl && !this.$refs.rootEl.contains(event.target)) {
                this.closeDropdown();
            }
        },
        moveHighlight(direction) {
            if (!this.open || this.results.length === 0) {
                return;
            }
            const count = this.results.length;
            this.highlightedIndex = (this.highlightedIndex + direction + count) % count;
        },
        selectHighlighted() {
            if (this.highlightedIndex >= 0 && this.results[this.highlightedIndex]) {
                this.goToUser(this.results[this.highlightedIndex]);
            }
        },
    },
};
</script>
