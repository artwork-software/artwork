<template>
    <div class="relative">
        <div class="flex" @click="open = ! open">
            <slot name="trigger"></slot><div v-if="!hideChevron"><IconChevronDown v-if="!open" class="h-5 w-5 text-text" aria-hidden="true"/> <IconChevronUp v-else class="h-5 w-5 text-text" aria-hidden="true"/></div>
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div v-show="open" class="fixed inset-0 z-40" @click="open = false">
        </div>

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95">
            <div v-show="open"
                    class="absolute z-50 mt-2 shadow-lg rounded-lg"
                    :class="[widthClass, alignmentClasses]"
                    style="display: none;"
                    @click="open = false">
                <div class="ring-1 ring-black/5 bg-surface-inverse rounded-lg" :class="contentClasses">
                    <slot name="content"></slot>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import {IconChevronDown, IconChevronUp} from "@tabler/icons-vue";
import {defineComponent, onMounted, onUnmounted, ref} from "vue";

export default defineComponent({
    props: {
        align: {
            default: 'right'
        },
        width: {
            default: '48'
        },
        contentClasses: {
            default: () => ['py-1', 'bg-white']
        },
        hideChevron:{
            default: false
        }
    },

    setup() {
        let open = ref(false)

        const closeOnEscape = (e) => {
            if (open.value && e.key === 'Escape') {
                open.value = false
            }
        }

        onMounted(() => document.addEventListener('keydown', closeOnEscape))
        onUnmounted(() => document.removeEventListener('keydown', closeOnEscape))

        return {
            open,
        }
    },
    components: {
      IconChevronUp,
      IconChevronDown
    },
    computed: {
        widthClass() {
            return {
                '48': 'w-48',
                '56': 'w-56',
                '64': 'w-64',
            }[this.width.toString()]
        },

        alignmentClasses() {
            if (this.align === 'left') {
                return 'origin-top-left left-0'
            } else if (this.align === 'right') {
                return 'origin-top-right right-0'
            } else {
                return 'origin-top'
            }
        },
    }
})
</script>
