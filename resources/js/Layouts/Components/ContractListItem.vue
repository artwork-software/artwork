<template>
    <div class="flex">
        <div class="w-full">
            <div class="flex items-center">
                <div @click="download" class="text-accent-600 cursor-pointer underline text-sm">{{ contract.name }}</div>
                <hr class="border-l border-l-surface-inverse h-5 mx-2">
                <div class="text-text text-sm">{{ contract.partner }}</div>
                <hr class="border-l border-l-surface-inverse h-5 mx-2">
                <div class="text-accent-600 text-sm"><a
                    :href="getEditHref(contract.project)">{{ contract.project?.name }}</a></div>
                <hr class="border-l border-l-surface-inverse h-5 mx-2">
                <div class="text-text text-sm">{{ contract.amount }}€</div>
            </div>
            <div class="flex items-center mt-1">
                <div class="text-text-subtle text-xs">{{ contract.company_type?.name }}</div>
                <hr v-if="contract.company_type" class="border-l border-l-secondary h-4 mx-2">
                <div class="text-text-subtle text-xs">{{ contract.contract_type?.name }}</div>
                <hr v-if="contract.contract_type" class="border-l border-l-secondary h-4 mx-2">
                <div class="text-text-subtle text-xs">
                    {{ contract.ksk_liable ? $t('KSK-liable') : $t('not KSK-liable') }}
                </div>
                <hr class="border-l border-l-secondary h-4 mx-2">
                <div class="text-text-subtle text-xs">
                    {{ contract.resident_abroad ? $t('Resident abroad') : $t('not resident abroad') }}
                </div>
            </div>
            <div class="flex items-center text-text-subtle text-xs mt-1">
                {{ contract.description }}
            </div>
        </div>
        <BaseMenu class="mt-3">
            <MenuItem v-slot="{ active }">
                <a href="#" @click="$emit('openEditContractModal', contract)"
                   :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                    <PropertyIcon name="IconEdit" stroke-width="1.5"
                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                              aria-hidden="true"/>
                    {{ $t('Edit')}}
                </a>
            </MenuItem>
            <MenuItem
                v-slot="{ active }">
                <a @click="$emit('openDeleteContractModal', contract)"
                   :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                    <PropertyIcon name="IconTrash" stroke-width="1.5"
                               class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                               aria-hidden="true"/>
                    {{ $t('Delete') }}
                </a>
            </MenuItem>
        </BaseMenu>
    </div>
</template>

<script>
import Permissions from "@/Mixins/Permissions.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {IconCopy, IconDotsVertical, IconDownload, IconEdit, IconTrash} from "@tabler/icons-vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "ContractListItem",
    mixins: [Permissions, IconLib],
    props: ['contract', 'first_project_tab_id'],
    emits: ['openDeleteContractModal', 'openEditContractModal'],
    components: {
        PropertyIcon,
        BaseMenu,
        IconCopy,
        Menu,
        MenuItem,
        MenuItems,
        MenuButton,
        IconDotsVertical, IconTrash, IconCopy, IconEdit,
        IconDownload
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
