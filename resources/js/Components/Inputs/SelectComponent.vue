<template>
    <div class="w-full flex flex-col relative">
        <label v-if="label" class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">
            {{ label }}
        </label>
        <Listbox as="div"
                 v-model="this.value"
                 @update:model-value="this.$emit('update:modelValue', this.value);"
                 by="id">
            <div class="relative">
                <ListboxButton
                    class="relative w-full flex items-center justify-between gap-1 text-left rounded-md border border-border bg-surface px-3 min-h-8 py-1 text-sm text-text focus:border-accent-600">
                    <div v-if="this.modelValue" class="truncate pr-6">
                        {{ this.modelValue[this.selectedPropertyToDisplay] }}
                    </div>
                    <div v-else class="truncate pr-6 text-text-muted">
                        {{ this.default ?? $t('Please select...') + '*' }}
                    </div>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                        <PropertyIcon name="IconChevronDown" class="size-4 text-text-muted"
                                         aria-hidden="true"/>
                    </span>
                </ListboxButton>
                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100"
                            leave-to-class="opacity-0">
                    <ListboxOptions
                        class="absolute z-50 text-xs subpixel-antialiased cursor-pointer mt-1 max-h-28 w-full overflow-auto rounded-lg bg-surface py-1 border border-border-subtle shadow-overlay">
                        <ListboxOption v-for="(option, index) in this.options"
                                       as="template"
                                       :key="'listbox-option-' + this.id + '-' + index"
                                       :value="option"
                                       v-slot="{ active, selected }">
                            <li :class="[active ? 'bg-accent-50 text-accent-700' : 'text-text', 'relative select-none py-1.5 pl-3 pr-9 min-h-8']">
                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                    {{ this.getterForOptionsToDisplay(option) }}
                                </span>
                                <span v-if="selected"
                                      :class="[active ? 'text-accent-700' : 'text-accent-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                    <PropertyIcon name="IconCheck" class="size-4" aria-hidden="true"/>
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
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import IconLib from "@/Mixins/IconLib.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default defineComponent({
    components: {
        PropertyIcon,
        Listbox,
        ListboxOption,
        ListboxOptions,
        ListboxButton
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
