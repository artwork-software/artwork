<template>
    <div class="flex items-center justify-between w-full">
        <div class="text-xs">
            <div class="flex-auto">
                <p>{{ entities.from ?? 0 }} - {{ entities.to ?? 0 }} von {{ entities.total }}</p>
            </div>
            <Menu as="div" class="relative inline-block text-base-600 hover:text-base-900">
                <MenuButton class="flex items-center me-4">
                    <p>Zeilen pro Seite: {{ entities.per_page }}</p>
                    <IconChevronDown class="w-5 h-5"/>
                </MenuButton>
                <transition enter-active-class="transition-enter-active"
                            enter-from-class="transition-enter-from"
                            enter-to-class="transition-enter-to"
                            leave-active-class="transition-leave-active"
                            leave-from-class="transition-leave-from"
                            leave-to-class="transition-leave-to">
                    <MenuItems
                        class="absolute origin-top-left z-50 bottom-0 right-0 mb-6 p-1 rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none">
                        <MenuItem v-for="entitiesToShow in entitiesPerPage" :key="entities" as="template" v-slot="{ active }">
                            <button @click="updateEntitiesPerPage(entitiesToShow)" :class="[active ? 'bg-gray-500 text-white' : 'text-gray-600', 'group flex items-center justify-center w-full rounded-md px-2 py-1 text-sm',]">
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
                                 class="mr-1 mb-1 px-2 py-1.5 text-sm leading-4 text-gray-400"/>
                            <a v-else
                               v-html="link.label"
                               class="cursor-pointer mr-1 mb-1 px-2 py-1.5 text-sm leading-4 rounded hover:bg-white"
                               :class="{ 'text-artwork-buttons-create': link.active }"
                               @click="updatePage(link.label, entities.current_page, entities.per_page)"/>
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
        updatePage(page, currentPage, entitiesPerPage) {
            if (page.includes('Weiter') || page.includes('Next')) {
                page = ++currentPage;
            } else if (page.includes('Zurück') || page.includes('Previous') || page.includes('Back')) {
                page = --currentPage;
            }

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
