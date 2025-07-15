<template>
    <div class="flex items-center group w-full">
        <Menu as="div" class="w-full h-full flex items-center" v-slot="{ open }" :open="menuOpen" @close="menuOpen = false">
            <Float auto-placement portal>
                <div class="w-full h-full">
                    <MenuButton class="w-full h-full">
                        <slot></slot>
                    </MenuButton>
                </div>
                <transition enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95">
                    <MenuItems v-show="canEditComponent" class="rounded-lg absolute bg-artwork-navigation-background shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none w-64 h-fit overflow-y-scroll">
                        <div class="text-white">
                            <div class="px-3 py-5">
                                <div class="flex items-center justify-between">
                                    <div class="flex gap-4 items-center" @click="openOrCloseFilter">
                                    <span class="flex" v-if="openFilter">
                                        <IconFilter class="h-5 w-5 text-white"/>
                                       <IconChevronDown class="h-5 w-5 text-white"/>
                                    </span>
                                        <span class="flex" v-else>
                                        <IconFilter class="h-5 w-5 text-white"/>
                                        <IconChevronUp class="h-5 w-5 text-white"/>
                                    </span>
                                    </div>
                                </div>
                                <div class="" v-if="openFilter">
                                    <div class="my-5">
                                        <div class="flex w-full mb-3">
                                            <input v-model="showIntern"
                                                   type="checkbox"
                                                   class="input-checklist-dark"/>
                                            <div :class="[showIntern ? 'xsWhiteBold' : 'xsLight', 'my-auto ml-2']">
                                                {{ $t('Internal employees') }}
                                            </div>
                                        </div>
                                        <div class="flex w-full mb-3">
                                            <input v-model="showExtern"
                                                   type="checkbox"
                                                   class="input-checklist-dark"/>
                                            <div :class="[showExtern ? 'xsWhiteBold' : 'xsLight', 'my-auto ml-2']">
                                                {{ $t('External employees') }}
                                            </div>
                                        </div>
                                        <div class="flex w-full mb-3">
                                            <input v-model="showProvider"
                                                   type="checkbox"
                                                   class="input-checklist-dark"/>
                                            <div :class="[showProvider ? 'xsWhiteBold' : 'xsLight', 'my-auto ml-2']">
                                                {{ $t('Service provider') }}
                                            </div>
                                        </div>
                                        <div>
                                            <div>
                                                <label for="account-number" class="block text-xs font-medium leading-6 text-white">
                                                    {{ $t('Search') }}
                                                </label>
                                                <div class="relative mt-2 rounded-md shadow-sm">
                                                    <input v-model="userSearch" ref="searchFieldUserSearch" type="text" name="account-number" id="account-number" class="block w-full rounded-lg border border-gray-600 py-1.5 pr-10 text-white ring-0 bg-darkGrayBg placeholder:text-gray-400 focus:border-gray-500 focus:ring-0 sm:text-sm sm:leading-6" />
                                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                        <IconSearch class="h-5 w-5 text-gray-400" aria-hidden="true" v-if="userSearch.length === 0" />
                                                        <IconX class="h-5 w-5 text-gray-400 cursor-pointer" aria-hidden="true" v-if="userSearch.length > 0" @click="userSearch = ''"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="my-2 h-0.5 w-full bg-[#3A374D]">
                                    </div>
                                </div>
                            </div>
                            <div class="max-h-72 overflow-y-scroll px-3 pb-3">
                                <div v-for="craft in craftWithUserFilteredByName" class="mt-1">
                                    <div @click="changeCraftVisibility(craft.id)" class="text-xs text-white flex cursor-pointer items-center h-6" v-if="craft.users.length > 0">
                                        {{ craft.name }}
                                        <IconChevronDown
                                            :class="closedCrafts.includes(craft.id) ? '' : 'rotate-180 transform'"
                                            class="h-4 w-4"
                                        />
                                    </div>
                                    <div v-for="user in craft.users" class="mb-1" v-if="!closedCrafts.includes(craft.id)">
                                        <div class="w-full p-2 text-white text-xs rounded-lg gap-2 relative h-8 flex items-center justify-between cursor-pointer" @click="createOnDropElementAndSave(user, craft)" :style="{backgroundColor: backgroundColorWithOpacityOld(craft.color )}">
                                            <div v-if="user.type === 0 || user.type === 1">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </div>
                                            <div v-else>
                                                {{ user.provider_name }}
                                            </div>

                                            <div v-if="user.type === 0 && user.is_freelancer || user.type === 1">
                                                <ToolTipComponent
                                                    icon="IconId"
                                                    icon-size="w-4 h-4"
                                                    tooltip-text="Freelancer*in"
                                                    direction="left"
                                                    classes="text-gray-300"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </MenuItems>
                </transition>
            </Float>
        </Menu>
        <slot name="xButton" />
    </div>
</template>

<script setup>

import {Float} from "@headlessui-float/vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {IconChevronDown, IconChevronUp, IconFilter, IconSearch, IconX} from "@tabler/icons-vue";
import {MenuButton, MenuItems, Menu} from "@headlessui/vue";
import Input from "@/Jetstream/Input.vue";
import {computed, nextTick, ref, watch} from "vue"
import {useColorHelper} from "@/Composeables/UseColorHelper.js";
const {backgroundColorWithOpacityOld, TextColorWithDarken} = useColorHelper()

const props = defineProps({
    craftsWithEntities: {
        type: Object,
        required: true,
    },
    canEditComponent: {
        type: Boolean,
        default: false
    }
})


const openFilter = ref(false)
const searchFieldUserSearch = ref(null)
const userSearch = ref('')
const showIntern = ref(false)
const showExtern = ref(false)
const showProvider = ref(false)
const closedCrafts = ref([])
const menuOpen = ref(false)

const emit = defineEmits(['createOnDropElementAndSave'])

const openOrCloseFilter = () => {
    openFilter.value = !openFilter.value

    if(openFilter.value) {
        nextTick(() => {
            searchFieldUserSearch.value.focus()
        })
    }
}


const craftWithUserFilterByType = computed(() => {
    return props.craftsWithEntities.map(craft => {
        return {
            ...craft,
            users: craft.users.filter(user => {
                if (!showIntern.value && !showExtern.value && !showProvider.value) {
                    return true;
                }
                if (showIntern.value && user.type === 0) {
                    return true;
                }
                if (showExtern.value && user.type === 1) {
                    return true;
                }
                return showProvider.value && user.type === 2;
            })
        }
    })
});


const craftWithUserFilteredByName = computed(() => {
    return craftWithUserFilterByType.value.map(craft => {
        return {
            ...craft,
            users: craft.users.filter(user => {
                if(user.type === 0 || user.type === 1) {
                    return user.first_name.toLowerCase().includes(userSearch.value.toLowerCase()) || user.last_name.toLowerCase().includes(userSearch.value.toLowerCase())
                }
                return user.provider_name.toLowerCase().includes(userSearch.value.toLowerCase())
            })
        }
    })
})

const changeCraftVisibility = (id) => {
    if (closedCrafts.value.includes(id)) {
        closedCrafts.value.splice(closedCrafts.value.indexOf(id), 1);
    } else {
        closedCrafts.value.push(id);
    }
}

const createOnDropElementAndSave = (user, craft) => {
    emit('createOnDropElementAndSave', user, craft)
}

watch(userSearch, () => {
    if(userSearch.value.length > 0) {
        closedCrafts.value = []
    }
})
</script>

<style scoped>

</style>
