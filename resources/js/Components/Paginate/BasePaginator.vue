<template>
    <div class="flex items-center justify-between w-full">
        <div class="text-xs text-text-muted">
            <div class="flex-auto">
                <p class="tabular-nums">{{ entities.from ?? 0 }} - {{ entities.to ?? 0 }} {{ $t('of') }} {{ entities.total }}</p>
            </div>
            <Menu as="div" class="relative inline-block text-text-muted hover:text-text">
                <MenuButton class="flex items-center me-4">
                    <p>{{ $t('Rows per page') }}: {{ entities.per_page }}</p>
                    <IconChevronDown class="w-5 h-5" stroke-width="1.5"/>
                </MenuButton>
                <transition enter-active-class="transition-enter-active"
                            enter-from-class="transition-enter-from"
                            enter-to-class="transition-enter-to"
                            leave-active-class="transition-leave-active"
                            leave-from-class="transition-leave-from"
                            leave-to-class="transition-leave-to">
                    <MenuItems
                        class="absolute origin-top-left z-50 bottom-0 right-0 mb-6 p-1 rounded-md bg-surface border border-border-subtle shadow-overlay">
                        <MenuItem v-for="entitiesToShow in entitiesPerPage" :key="entitiesToShow" as="template" v-slot="{ active }">
                            <button @click="updateEntitiesPerPage(entitiesToShow)" :class="[active ? 'bg-surface-sunken text-text' : 'text-text-muted', 'group flex items-center justify-center w-full rounded-md px-2 py-1 text-sm tabular-nums',]">
                                {{ entitiesToShow }}
                            </button>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu>
        </div>
        <div class="flex items-center gap-1">
            <!-- Pagination -->
            <div class="flex items-center">
                <div v-if="entities.links.length > 3">
                    <div class="flex flex-wrap -mb-1">
                        <div v-for="(link, key) in entities.links" :key="key">
                            <div v-if="link.url === null"
                                 v-html="link.label"
                                 class="mr-1 mb-1 inline-flex items-center justify-center min-w-[26px] h-[26px] px-1.5 text-[13px] leading-4 tabular-nums text-text-subtle"/>
                            <a v-else
                               v-html="link.label"
                               class="cursor-pointer mr-1 mb-1 inline-flex items-center justify-center min-w-[26px] h-[26px] px-1.5 text-[13px] leading-4 tabular-nums rounded-md"
                               :class="link.active ? 'bg-surface-inverse text-white' : 'text-text hover:bg-surface-hover'"
                               @click="updatePage(link, entities.current_page, entities.per_page)"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {IconChevronDown} from "@tabler/icons-vue";
import {Link, router, usePage} from "@inertiajs/vue3";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";

export default {
    name: "BasePaginator",
    emits: ['updateEntitiesPerPage', 'updatePage'],
    components: {
        Link,
        IconChevronDown,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
    },
    props: {
        entities: {
            type: Object,
            required: true
        },
        propertyName: {
            type: String,
            required: true
        },
        emitUpdateEntitiesPerPage: {
            type: Boolean,
            required: false,
            default: false
        },
        useCustomUrlParams: {
            type: Boolean,
            required: false,
            default: false
        },
    },
    data() {
        return {
            entitiesPerPage: [1, 5, 10, 25, 50, 100],
        }
    },
    methods: {
        usePage,
        resolvePageFromLink(link, currentPage) {
            // Primär: page-Query-Parameter aus link.url parsen (label-unabhängig, sprachneutral)
            if (link?.url) {
                try {
                    const parsedUrl = new URL(link.url, window.location.origin);
                    const pageParam = parsedUrl.searchParams.get('page');

                    if (pageParam !== null && !isNaN(Number(pageParam))) {
                        return Number(pageParam);
                    }
                } catch (e) {
                    // fällt auf Label-Heuristik zurück
                }
            }

            // Fallback: altes Label-Verhalten
            let page = link?.label ?? '';

            if (page.includes('Weiter') || page.includes('Next')) {
                return currentPage + 1;
            }

            if (page.includes('Zurück') || page.includes('Previous') || page.includes('Back')) {
                return currentPage - 1;
            }

            return page;
        },
        updatePage(link, currentPage, entitiesPerPage) {
            const page = this.resolvePageFromLink(link, currentPage);

            if (this.emitUpdateEntitiesPerPage) {
                this.$emit('updatePage', page, entitiesPerPage);

                return;
            }

            let dataObject = {};

            if (this.useCustomUrlParams) {
                dataObject = {
                    page: page,
                    [this.propertyName + 'PerPage']: entitiesPerPage,
                }
            } else {
                dataObject = {
                    page: page,
                    entitiesPerPage: entitiesPerPage,
                }
            }

            router.reload(
                {
                    only: [this.propertyName],
                    data: dataObject
                }
            );
        },
        updateEntitiesPerPage(entitiesToShow) {
            if (this.emitUpdateEntitiesPerPage) {
                this.$emit('updateEntitiesPerPage', entitiesToShow);
                return;
            }

            let dataObject = {};

            if (this.useCustomUrlParams) {
                dataObject = {
                    page: 1,
                    [this.propertyName + 'PerPage']: entitiesToShow,
                }
            } else {
                dataObject = {
                    page: 1,
                    entitiesPerPage: entitiesToShow,
                }
            }

            router.reload({
                only: [this.propertyName],
                data: dataObject
            })
        },
    }
}
</script>
