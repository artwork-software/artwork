<template>
    <div class="flex">
        <div class="w-full">
            <div class="flex items-center">
                <div @click="download" class="text-artwork-buttons-create cursor-pointer underline text-sm">{{ contract.name }}</div>
                <hr class="border-l border-l-primary h-5 mx-2">
                <div class="text-primary text-sm">{{ contract.partner }}</div>
                <hr class="border-l border-l-primary h-5 mx-2">
                <div class="text-artwork-buttons-create text-sm"><a
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
        <BaseMenu class="mt-3">
            <MenuItem v-slot="{ active }">
                <a href="#" @click="$emit('openEditContractModal', contract)"
                   :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                    <IconEdit stroke-width="1.5"
                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                              aria-hidden="true"/>
                    {{ $t('Edit')}}
                </a>
            </MenuItem>
            <MenuItem
                v-slot="{ active }">
                <a @click="$emit('openDeleteContractModal', contract)"
                   :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                    <IconTrash stroke-width="1.5"
                               class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                               aria-hidden="true"/>
                    {{ $t('Delete') }}
                </a>
            </MenuItem>
        </BaseMenu>
    </div>
</template>

<script>
import {
    DownloadIcon, DuplicateIcon, PencilAltIcon, TrashIcon
} from '@heroicons/vue/outline';
import Permissions from "@/Mixins/Permissions.vue";
import {DotsVerticalIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {IconCopy} from "@tabler/icons-vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";

export default {
    name: "ContractListItem",
    mixins: [Permissions, IconLib],
    props: ['contract', 'first_project_tab_id'],
    emits: ['openDeleteContractModal', 'openEditContractModal'],
    components: {
        BaseMenu,
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
