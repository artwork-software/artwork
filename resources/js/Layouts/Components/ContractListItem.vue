<template>
    <div class="flex">
        <div class="w-full">
            <div class="flex items-center">
                <div @click="download" class="text-buttonBlue cursor-pointer underline text-sm">{{ contract.name }}</div>
                <hr class="border-l border-l-primary h-5 mx-2">
                <div class="text-primary text-sm">{{ contract.partner }}</div>
                <hr class="border-l border-l-primary h-5 mx-2">
                <div class="text-buttonBlue text-sm"><a
                    :href="getEditHref(contract.project)">{{ contract.project?.name }}</a></div>
                <hr class="border-l border-l-primary h-5 mx-2">
                <div class="text-primary text-sm">{{ contract.amount }}€</div>
            </div>
            <div class="flex items-center mt-1">
                <div class="text-secondary text-xs">{{ contract.company_type?.name }}</div>
                <hr v-if="contract.company_type" class="border-l border-l-secondary h-4 mx-2">
                <div class="text-secondary text-xs">{{ contract.contract_type?.name }}</div>
                <hr v-if="contract.contract_type" class="border-l border-l-secondary h-4 mx-2">
                <div class="text-secondary text-xs">
                    {{ contract.ksk_liable ? $t('KSK-liable') : $t('not KSK-liable') }}
                </div>
                <hr class="border-l border-l-secondary h-4 mx-2">
                <div class="text-secondary text-xs">
                    {{ contract.resident_abroad ? $t('Resident abroad') : $t('not resident abroad') }}
                </div>
            </div>
            <div class="flex items-center text-secondary text-xs mt-1">
                {{ contract.description }}
            </div>
        </div>
        <Menu as="div" class="my-auto mt-3 relative">
            <div class="flex items-center -mt-1">
                <MenuButton
                    class="flex bg-tagBg p-0.5 rounded-full">
                    <IconDotsVertical stroke-width="1.5"
                        class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                        aria-hidden="true"/>
                </MenuButton>
            </div>
            <transition enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                <MenuItems
                    class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                    <div class="py-1">
                        <MenuItem v-slot="{ active }">
                            <a href="#" @click="$emit('openEditContractModal', contract)"
                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                <IconEdit stroke-width="1.5"
                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                    aria-hidden="true"/>
                                {{ $t('Edit')}}
                            </a>
                        </MenuItem>
                        <MenuItem
                            v-slot="{ active }">
                            <a @click="$emit('openDeleteContractModal', contract)"
                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                <IconTrash stroke-width="1.5"
                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                    aria-hidden="true"/>
                                {{ $t('Delete') }}
                            </a>
                        </MenuItem>
                    </div>
                </MenuItems>
            </transition>
        </Menu>
    </div>
</template>

<script>
import {
    DownloadIcon, DuplicateIcon, PencilAltIcon, TrashIcon
} from '@heroicons/vue/outline';
import Permissions from "@/mixins/Permissions.vue";
import {DotsVerticalIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {IconCopy} from "@tabler/icons-vue";
import IconLib from "@/mixins/IconLib.vue";

export default {
    name: "ContractListItem",
    mixins: [Permissions, IconLib],
    props: ['contract', 'first_project_tab_id'],
    emits: ['openDeleteContractModal', 'openEditContractModal'],
    components: {
        IconCopy,
        Menu,
        MenuItem,
        MenuItems,
        MenuButton,
        DotsVerticalIcon, TrashIcon, DuplicateIcon, PencilAltIcon,
        DownloadIcon
    },
    methods: {
        download() {
            let link = document.createElement('a');
            link.href = route('contracts.download', {contract: this.contract});
            link.target = '_blank';
            link.click();
        },
        getEditHref(project) {
            return route('projects.tab', {project: project?.id, projectTab: this.first_project_tab_id});
        },
    }
}
</script>

<style scoped>

</style>
