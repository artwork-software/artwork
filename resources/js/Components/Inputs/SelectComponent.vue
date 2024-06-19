<template>
    <div class="w-full flex flex-col relative mt-5">
        <PlaceholderLabel :label="this.label"/>
        <Listbox as="div"
                 v-model="this.value"
                 @update:model-value="this.$emit('update:modelValue', this.value);"
                 by="id">
            <div class="relative">
                <ListboxButton
                    class="w-full h-12 pl-1 pt-1 text-left text-xs subpixel-antialiased font-normal border-gray-300 inputMain xsDark disabled:border-none">
                    <span v-if="this.modelValue" class="truncate">
                        {{ this.modelValue[this.selectedPropertyToDisplay] }}
                    </span>
                    <span v-else class="truncate text-secondary">
                        {{ this.default ?? $t('Please select...') + '*' }}
                    </span>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                        <IconChevronDown class="h-5 w-5 text-primary"
                                         aria-hidden="true"/>
                    </span>
                </ListboxButton>
                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100"
                            leave-to-class="opacity-0">
                    <ListboxOptions
                        class="absolute z-50 text-xs subpixel-antialiased cursor-pointer mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                        <ListboxOption as="template" v-for="(option, index) in this.options"
                                       :key="'listbox-option-' + this.id + '-' + index"
                                       :value="option"
                                       v-slot="{ active, selected }">
                            <li :class="[active ? 'bg-artwork-buttons-create text-white' : 'text-gray-900', 'relative select-none py-2 pl-3 pr-9']">
                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                    {{ this.getterForOptionsToDisplay(option) }}
                                </span>
                                <span v-if="selected"
                                      :class="[active ? 'text-white' : 'text-artwork-buttons-create', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                    <IconCheck class="h-5 w-5" aria-hidden="true"/>
                                </span>
                            </li>
                        </ListboxOption>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>
    </div>
</template>

<script>

import {defineComponent} from "vue";
import PlaceholderLabel from "@/Components/Inputs/Labels/PlaceholderLabel.vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import IconLib from "@/Mixins/IconLib.vue";

export default defineComponent({
    components: {
        Listbox,
        ListboxOption,
        ListboxOptions,
        ListboxButton,
        PlaceholderLabel
    },
    mixins: [
        IconLib
    ],
    props: [
        'id',
        'label',
        'modelValue',
        'default',
        'options',
        'selectedPropertyToDisplay',
        'getterForOptionsToDisplay',
    ],
    data() {
        return {
            //this is necessary because Listbox requries v-model, we are not able to bind modelValue
            //prop to value attribute of Listbox because Listbox is not a native input element
            //see v-model documentation regarding two-way binding https://vuejs.org/guide/components/v-model.html
            //so we simply proxy the value but still emit the desired update:modelValue event
            value: this.modelValue
        }
    },
    emits: [
        'update:modelValue'
    ]
})
</script>
