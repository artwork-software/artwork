<template>
    <Menu as="div" class="inline-block" :class="!noRelative ? 'relative' : ''">
        <div class="flex items-center justify-center w-full font-semibold text-artwork-buttons-context" ref="menuButtonRef">
            <MenuButton @click="toggleMenu">
                <IconDotsVertical v-if="!showSortIcon"
                                  stroke-width="1.5"
                                  class="flex-shrink-0"
                                  aria-hidden="true"
                                  :class="[dotsColor, dotsSize]"
                />
                <ToolTipComponent
                    v-else
                    direction="bottom"
                    :tooltip-text="$t('Sorting')"
                    icon="IconSortDescending"
                    icon-size="h-8 w-8"
                    :white-icon="whiteIcon"
                />
            </MenuButton>
        </div>

        <transition enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95">
            <MenuItems v-if="menuVisible"
                       class="z-50 rounded-lg bg-artwork-navigation-background shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                       :style="menuStyles">
                <div>
                    <slot />
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>

<script>
import { defineComponent, ref, onMounted, onBeforeUnmount } from 'vue';
import { Menu, MenuButton, MenuItems } from '@headlessui/vue';
import IconLib from '@/Mixins/IconLib.vue';
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

export default defineComponent({
    name: 'BaseMenu',
    mixins: [IconLib],
    components: {
        ToolTipComponent,
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
            default: false,
        },
        noRelative: {
            type: Boolean,
            default: false,
        },
        showSortIcon: {
            type: Boolean,
            default: false,
        },
        menuWidth: {
            type: String,
            default: 'w-56',
        },
        whiteIcon: {
            type: Boolean,
            required: false,
            default: false
        },
    },
    setup(props) {
        const menuButtonRef = ref(null);
        const menuVisible = ref(false);
        const menuStyles = ref({});

        const toggleMenu = () => {
            if (menuVisible.value) {
                hideMenu();
            } else {
                updateMenuPosition();
            }
        };

        const updateMenuPosition = () => {
            const buttonRect = menuButtonRef.value.getBoundingClientRect();
            const menuWidth = 224; // w-56 (56 * 4px)
            const windowWidth = window.innerWidth;

            let leftPosition;

            if (!props.right) {
                // Menu opens to the right
                leftPosition = buttonRect.right - menuWidth;

                // If menu goes off the screen on the left, adjust it
                if (leftPosition < 0) {
                    leftPosition = 0;
                }
            } else {
                // Menu opens to the left
                leftPosition = buttonRect.left;

                // If menu goes off the screen on the right, adjust it
                if (leftPosition + menuWidth > windowWidth) {
                    leftPosition = windowWidth - menuWidth;
                }
            }

            menuStyles.value = {
                position: 'fixed',
                top: `${buttonRect.bottom}px`,
                left: `${leftPosition}px`,
                zIndex: 50,
            };

            menuVisible.value = true;
        };

        const hideMenu = () => {
            menuVisible.value = false;
        };

        const handleScrollOrResize = () => {
            if (menuVisible.value) {
                updateMenuPosition();
            }
        };

        onMounted(() => {
            window.addEventListener('scroll', handleScrollOrResize);
            window.addEventListener('resize', handleScrollOrResize);
        });

        onBeforeUnmount(() => {
            window.removeEventListener('scroll', handleScrollOrResize);
            window.removeEventListener('resize', handleScrollOrResize);
        });

        return {
            menuButtonRef,
            menuVisible,
            menuStyles,
            toggleMenu,
            hideMenu,
        };
    },
});
</script>
