<template>
    <div class="w-full flex flex-col relative">
        <Listbox as="div"
                 v-model="this.value"
                 @update:model-value="this.$emit('update:modelValue', this.value);"
                 by="id">
            <div class="relative">
                <ListboxButton
                    class="menu-button-no-padding relative">
                    <div v-if="this.modelValue" class="truncate">
                        <div class="top-2 left-4 absolute text-gray-500 text-xs">
                            {{ label }}
                        </div>
                        <div class="pt-6 pb-2">
                            {{ this.modelValue[this.selectedPropertyToDisplay] }}
                        </div>
                    </div>
                    <div v-else class="truncate text-secondary">
                        <div class="top-2 left-4 absolute text-gray-500 text-xs">
                            {{ label }}
                        </div>
                        <div class="pt-6 pb-2">
                            {{ this.default ?? $t('Please select...') + '*' }}
                        </div>
                    </div>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                        <PropertyIcon name="IconChevronDown" class="h-5 w-5 text-primary"
                                         aria-hidden="true"/>
                    </span>
                </ListboxButton>
                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100"
                            leave-to-class="opacity-0">
                    <ListboxOptions
                        class="absolute z-50 text-xs subpixel-antialiased cursor-pointer mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-gray-300 ring-opacity-5 focus:outline-none">
                        <ListboxOption v-for="(option, index) in this.options"
                                       as="template"
                                       :key="'listbox-option-' + this.id + '-' + index"
                                       :value="option"
                                       v-slot="{ active, selected }">
                            <li :class="[active ? 'bg-artwork-buttons-create text-white' : 'text-gray-900', 'relative select-none py-2 pl-3 pr-9']">
                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                    {{ this.getterForOptionsToDisplay(option) }}
                                </span>
                                <span v-if="selected"
                                      :class="[active ? 'text-white' : 'text-artwork-buttons-create', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                    <PropertyIcon name="IconCheck" class="h-5 w-5" aria-hidden="true"/>
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
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default defineComponent({
    components: {
        PropertyIcon,
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
