<template>
    <Menu as="div" class="z-50" :class="noRelative ? '' : 'relative'">
        <div class="flex">
            <MenuButton ref="menuButtonRef" @click="handleMenuButtonClick" class="flex">
                <IconDotsVertical
                    stroke-width="1.5"
                    class="flex-shrink-0"
                    aria-hidden="true"
                    :class="[dotsColor, dotsSize]"
                />
            </MenuButton>
        </div>
        <Teleport to="body">
            <transition
                enter-active-class="transition-enter-active"
                enter-from-class="transition-enter-from"
                enter-to-class="transition-enter-to"
                leave-active-class="transition-leave-active"
                leave-from-class="transition-leave-from"
                leave-to-class="transition-leave-to"
            >
                <div v-if="isOpen" :style="positionStyles" class="absolute z-50">
                    <MenuItems ref="menuRef" class="rounded-lg w-80 shadow-lg bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                        <div class="py-1">
                            <slot />
                        </div>
                    </MenuItems>
                </div>
            </transition>
        </Teleport>
    </Menu>
</template>

<script>
import { ref, watch, nextTick, defineComponent } from 'vue';
import { Menu, MenuButton, MenuItems } from '@headlessui/vue';
import IconLib from '@/Mixins/IconLib.vue';

export default defineComponent({
    name: 'BaseMenu',
    mixins: [IconLib],
    components: {
        Menu,
        MenuButton,
        MenuItems,
    },
    props: {
        dotsColor: {
            type: String,
            default: 'text-artwork-navigation-text',
        },
        dotsSize: {
            type: String,
            default: 'h-6 w-6',
        },
        right: {
            type: Boolean,
            default: true,
        },
        noRelative: {
            type: Boolean,
            default: false,
        },
    },
    setup(props) {
        const menuRef = ref(null);
        const menuButtonRef = ref(null);
        const isOpen = ref(false);
        const positionStyles = ref({});

        const calculatePosition = () => {
            if (menuButtonRef.value && menuRef.value) {
                const buttonEl = menuButtonRef.value;
                const menuEl = menuRef.value;

                if (buttonEl && buttonEl.getBoundingClientRect && menuEl && menuEl.getBoundingClientRect) {
                    const buttonRect = buttonEl.getBoundingClientRect();
                    const viewportWidth = window.innerWidth;

                    const top = buttonRect.bottom + window.scrollY;
                    let left = buttonRect.left + window.scrollX;

                    // Ensure the menu does not overflow the viewport width
                    const rightSpace = viewportWidth - buttonRect.right;
                    if (props.right && menuEl.offsetWidth > rightSpace) {
                        left = buttonRect.right - menuEl.offsetWidth + window.scrollX;
                    }

                    positionStyles.value = {
                        top: `${top}px`,
                        left: `${left}px`,
                        position: 'absolute',
                    };
                }
            }
        };

        watch(isOpen, async (newVal) => {
            if (newVal) {
                await nextTick();
                calculatePosition();
            }
        });

        const handleMenuButtonClick = () => {
            isOpen.value = !isOpen.value;
        };

        return {
            menuRef,
            menuButtonRef,
            isOpen,
            positionStyles,
            handleMenuButtonClick,
        };
    },
});
</script>

<style scoped>
</style>
