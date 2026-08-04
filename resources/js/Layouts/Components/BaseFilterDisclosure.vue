<template>
    <Disclosure v-slot="{ open }">
        <DisclosureButton
            class="flex w-full py-2 justify-between rounded-lg bg-surface-inverse text-left text-sm font-medium focus:outline-none focus-visible:ring-accent-600"
        >
            <span :class="open ? 'font-bold text-white' : 'font-medium text-text-subtle'">{{ title }}</span>
            <IconChevronDown stroke-width="1.5"
                :class="open ? 'rotate-180 transform' : ''"
                class="h-4 w-4 mt-0.5 text-white"
            />
        </DisclosureButton>
        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
            <slot></slot>
        </DisclosurePanel>
    </Disclosure>
</template>

<script>
import {IconChevronDown} from "@tabler/icons-vue";
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel
} from "@headlessui/vue";

import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "BaseFilterDisclosure",
    mixins: [Permissions, IconLib],
    components: {
        Disclosure,
        DisclosurePanel,
        DisclosureButton,
        IconChevronDown
    },
    props: {
        title: String
    }
}
</script>

<style scoped>

</style>
