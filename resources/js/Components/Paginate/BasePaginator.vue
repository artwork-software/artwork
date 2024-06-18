<script>
import {IconChevronDown} from "@tabler/icons-vue";
import {Link} from "@inertiajs/vue3";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {router} from "@inertiajs/vue3";

export default {
    name: "BasePaginator",
    components: {Link, IconChevronDown,  Menu,
        MenuButton,
        MenuItem,
        MenuItems,},
    props: {
        entities: {
            type: Object,
            required: true
        },
        propertyName: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            entitiesPerPage: [10, 25, 50, 100],
        }
    },
    methods: {
        updateEntitiesPerPage(entitiesToShow) {
            router.reload({
                only: [this.propertyName],
                data: {
                    entitiesPerPage: entitiesToShow,
                    page: 1
                }
            })
        },
    }
}
</script>

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

                <transition enter-active-class="transition duration-100 ease-out"
                            enter-from-class="transform scale-95 opacity-0"
                            enter-to-class="transform scale-100 opacity-100"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="transform scale-100 opacity-100"
                            leave-to-class="transform scale-95 opacity-0">
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
                        <template v-for="(link, key) in entities.links" :key="key">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-2 py-1.5 text-sm leading-4 text-gray-400" v-html="link.label"></div>
                            <Link v-else class="mr-1 mb-1 px-2 py-1.5 text-sm leading-4 rounded hover:bg-white" :class="{ 'text-artwork-buttons-create': link.active }" :href="link.url" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<style scoped>

</style>
