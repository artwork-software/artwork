<template>
    <div class="w-full">
        <div v-if="items.length > 0" v-for="(item,index) in items" :key="item.id" class="flex items-center justify-between border-b-2 border-border-subtle">
            <div class="py-5 flex items-center">
                <BaseFilterTag :filter="item" @remove-filter="forceDelete(item)" />
                <div class="ml-2">{{type}}</div>
            </div>
            <div class="w-1/12 flex justify-end">
                <BaseMenu>
                    <MenuItem v-slot="{ active }" class="z-10">
                        <a @click="restoreItem(item)"
                           :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconRefresh
                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                                aria-hidden="true"/>
                            {{ $t('Restore')}}
                        </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }" class="z-10">
                        <a @click="forceDelete(item)"
                           :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconTrash
                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                                aria-hidden="true"/>
                            {{ $t('Delete permanently')}}
                        </a>
                    </MenuItem>
                </BaseMenu>

            </div>
        </div>
    </div>
</template>

<script>
import {IconDotsVertical, IconRefresh, IconTrash} from "@tabler/icons-vue";

import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import Permissions from "@/Mixins/Permissions.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";

export default {
    mixins: [Permissions],
    props: ['items', 'type', 'model'],
    components: {
        BaseMenu,
        BaseFilterTag,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        IconDotsVertical,
        IconRefresh,
        IconTrash,
    },
    methods: {
        forceDelete(item){
            this.$inertia.delete(route(`${this.model}.force`, { id: item.id}))
        },
        restoreItem(item){
            this.$inertia.patch(route(`${this.model}.restore`, { id: item.id}))
        }

    }
}

</script>

<style scoped>

</style>
