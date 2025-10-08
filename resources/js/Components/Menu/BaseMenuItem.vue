<template>
    <MenuItem v-slot="{ active }">
        <Link :href="link" v-if="asLink"
           :class="[whiteMenuBackground ? 'hover:text-artwork-buttons-create' : 'hover:text-white text-white', icon === IconTrash || icon === 'IconTrash' ? 'text-red-400' : ' text-gray-500', 'group flex items-center text-xs px-3 py-2 subpixel-antialiased cursor-pointer rounded-lg hover:bg-gray-100']">
            <PropertyIcon :name="icon" class="mr-2 h-4 w-4" :class="whiteMenuBackground ? 'group-hover:text-artwork-buttons-create' : ' group-hover:text-artwork-buttons-hover'" aria-hidden="true"/>
            {{ withoutTranslation ? title : $t(title) }}
        </Link>
        <div v-else
           :class="[whiteMenuBackground ? 'hover:text-artwork-buttons-create' : 'hover:text-white text-white', icon === IconTrash || icon === 'IconTrash' ? 'text-red-400' : ' text-gray-500' , 'group flex items-center text-xs px-3 py-2 subpixel-antialiased cursor-pointer rounded-lg hover:bg-gray-100']">
            <PropertyIcon :name="icon" class="mr-2 h-4 w-4" :class="whiteMenuBackground ? 'group-hover:text-artwork-buttons-create' : 'group-hover:text-artwork-buttons-hover'" aria-hidden="true"/>
            {{ withoutTranslation ? title : $t(title) }}
        </div>
    </MenuItem>
</template>

<script setup lang="ts">

import {MenuItem} from "@headlessui/vue";
import {Link} from "@inertiajs/vue3";
import {computed} from "vue";
import {IconEdit, IconTrash} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
const props = defineProps({
    asLink: {
        type: Boolean,
        default: false
    },
    link: {
        type: String,
        default: ''
    },
    icon: {
        // erlaubt String oder echte Komponente (Function/Object)
        type: [String, Function, Object] as PropType<string | Component>,
        default: null,
    },
    title: {
        type: String,
        default: ''
    },
    withoutTranslation: {
        type: Boolean,
        required: false,
        default: false
    },
    whiteMenuBackground: {
        type: Boolean,
        default: false
    }
})


function toITabler(name: string) {
    // "IconEdit" -> "i-tabler-edit"
    return 'i-tabler-' + name.replace(/^Icon/, '')
        .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
        .toLowerCase()
}

const resolvedIcon = computed(() => {
    if (!props.icon) return IconEdit
    return typeof props.icon === 'string' ? toITabler(props.icon) : props.icon
})
</script>

<style scoped>

</style>
