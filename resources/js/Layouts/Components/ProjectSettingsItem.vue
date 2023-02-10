<template>
    <div class="mt-16 max-w-2xl">
        <h2 class="headline2 my-2">{{ title }}</h2>
        <div class="xsLight">
            {{ description }}
        </div>
    </div>
    <div class="mt-8 flex w-full flex-wrap">
        <div class="relative flex max-w-lg w-full">
            <input id="input" v-model="input" type="text" @keyup.enter="add"
                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                   placeholder="placeholder"/>
            <label for="input"
                   class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">
                {{ inputLabel }}
            </label>
            <div class="m-2 -ml-8 -mt-1">
                <button
                    :class="[input === '' ? 'bg-secondary': 'bg-buttonBlue hover:bg-buttonHover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-secondaryHover']"
                    @click="add" :disabled="!input">
                    <CheckIcon class="h-5 w-5"></CheckIcon>
                </button>
            </div>
        </div>
        <div class="flex flex-wrap w-full max-w-xl">
                        <span v-for="item in items"
                              class="rounded-full items-center font-medium text-tagText
                                            border bg-tagBg border-tag px-3 mt-2 text-sm mr-1 mb-1 h-8 inline-flex">
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
import {CheckIcon} from "@heroicons/vue/solid";
export default {
    name: "ProjectSettingsItem",
    props: {
        title: String,
        description: String,
        items: Array,
        inputLabel: String
    },
    components: {
        XIcon, CheckIcon
    },
    data() {
        return {
            input: ''
        }
    },
    methods: {
        add() {
            this.$emit('add', this.input)
            this.input = ''
        }
    }
}
</script>

<style scoped>

</style>
