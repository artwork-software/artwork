<template>
    <div class="mt-16 max-w-2xl">
        <h2 class="headline2 my-2">{{ title }}</h2>
        <div class="xsLight">
            {{ description }}
        </div>
    </div>
    <div class="mt-8 flex w-full flex-wrap">
        <div class="relative flex max-w-lg w-full items-center">
            <div>
                <Listbox as="div" class="flex mr-2" v-model="selectedColor">
                    <ListboxButton>
                        <button class="w-8 h-8 flex justify-center items-center rounded-full"
                                :class="selectedColor=== 'stateColorDefault' ? 'stateColorDefault border border-1' : selectedColor"
                                @click="openColor = !openColor">
                            <ChevronUpIcon v-if="openColor" class="h-3 w-3 my-auto"
                                           :class="selectedColor === 'stateColorDefault' ? 'text-black' : 'text-white'"></ChevronUpIcon>
                            <ChevronDownIcon v-else
                                             class="h-3 w-3 text-white my-auto"
                                             :class="selectedColor === 'stateColorDefault' ? 'text-black' : 'text-white'"></ChevronDownIcon>
                        </button>
                    </ListboxButton>

                    <transition leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <ListboxOptions
                            class="absolute w-24 z-10 mt-12 bg-primary shadow-lg max-h-64 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                            <ListboxOption as="template" class=""
                                           v-for="color in colors"
                                           :key="color"
                                           :value="color" v-slot="{ active, selected }">
                                <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 text-sm subpixel-antialiased']"
                                    @click="">
                                    <div class="flex">
                                                                    <span
                                                                        :class="[selected ? 'xsWhiteBold' : 'font-normal', 'block truncate']">
                                                                        <span
                                                                            class="block truncate items-center ml-3 flex rounded-full h-10 w-10"
                                                                            :class="color">
                                                                        </span>
                                                                    </span>
                                    </div>
                                    <span
                                        :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                                    <CheckIcon v-if="selected"
                                                                               class="h-5 w-5 flex text-success"
                                                                               aria-hidden="true"/>
                                                                </span>
                                </li>
                            </ListboxOption>
                        </ListboxOptions>
                    </transition>
                </Listbox>
            </div>
            <div class="w-full">
                <input id="inputState" v-model="input" type="text" @keyup.enter="add"
                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                       placeholder="placeholder"/>
                <label for="input"
                       class="absolute left-10 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">
                    {{ inputLabel }}
                </label>
            </div>
            <div class="m-2 -ml-8 -mt-1">
                <button
                    :class="[input === '' ? 'bg-secondary': 'bg-artwork-buttons-create hover:bg-artwork-buttons-hover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-white']"
                    @click="add" :disabled="!input">
                    <CheckIcon class="h-5 w-5"></CheckIcon>
                </button>
            </div>
        </div>
        <div class="flex flex-wrap w-full max-w-xl">
                        <span v-for="item in items"
                              class="rounded-full items-center font-medium px-3 mt-2 text-sm mr-1 mb-1 h-8 inline-flex" :class="item.color">
                            {{ item.name }}
                            <button type="button" @click="$emit('openDeleteModal', item)">
                                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>

        </div>
    </div>
</template>

<script>
import {XIcon} from "@heroicons/vue/outline"
import {CheckIcon, ChevronDownIcon, ChevronUpIcon} from "@heroicons/vue/solid";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import Permissions from "@/Mixins/Permissions.vue";
export default {
    name: "ProjectSettingState",
    mixins: [Permissions],
    props: {
        title: String,
        description: String,
        items: Array,
        inputLabel: String
    },
    components: {
        XIcon, CheckIcon,Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon,
        ChevronUpIcon,
    },
    data() {
        return {
            input: '',
            colors: {
                stateColorDefault: 'stateColorDefault',
                stateColorRed: 'stateColorRed',
                stateColorPink: 'stateColorPink',
                stateColorOrange: 'stateColorOrange',
                stateColorYellow: 'stateColorYellow',
                stateColorGreen: 'stateColorGreen',
                stateColorDarkGreen: 'stateColorDarkGreen',
                stateColorDodgeBlue: 'stateColorDodgeBlue',
                stateColorBlue: 'stateColorBlue',
                stateColorGreenBlue: 'stateColorGreenBlue',
                stateColorDarkBlue: 'stateColorDarkBlue'
            },
            openColor: false,
            selectedColor: 'stateColorDefault'
        }
    },
    methods: {
        add() {
            this.$emit('add', this.input, this.selectedColor)
            this.input = ''
            this.selectedColor = this.colors.stateColorDefault
        }
    }
}
</script>

<style scoped>
.whiteColumn {
    background-color: #FCFCFBFF;
}

.greenColumn {
    background-color: #50908E;
    border: 2px solid #1FC687;
}

.yellowColumn {
    background-color: #F0B54C;
}

.redColumn {
    background-color: #D84387;
}

.lightGreenColumn {
    background-color: #35A965;
}
</style>
