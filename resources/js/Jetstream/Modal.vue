<template>
    <teleport to="body">
        <transition leave-active-class="duration-200">
            <div v-show="show" class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" scroll-region>
                <transition enter-active-class="transition-enter-active"
                            enter-from-class="transition-enter-from"
                            enter-to-class="transition-enter-to"
                            leave-active-class="transition-leave-active"
                            leave-from-class="transition-leave-from"
                            leave-to-class="transition-leave-to">
                </transition>
                <transition enter-active-class="transition-enter-active"
                            enter-from-class="transition-enter-from"
                            enter-to-class="transition-enter-to"
                            leave-active-class="transition-leave-active"
                            leave-from-class="transition-leave-from"
                            leave-to-class="transition-leave-to">
                    <div v-show="show" class="mb-6 bg-white rounded-lg shadow-xl transform transition-all sm:w-full sm:mx-auto" :class="maxWidthClass">
                        <slot v-if="show"></slot>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>

<script>
import { defineComponent, onMounted, onUnmounted } from "vue";

export default defineComponent({
        emits: ['close'],

        props: {
            show: {
                default: false
            },
            maxWidth: {
                default: '2xl'
            },
            closeable: {
                default: true
            },
        },

        watch: {
            show: {
                immediate: true,
                handler: (show) => {
                    if (show) {
                        document.body.style.overflow = 'hidden'
                    } else {
                        document.body.style.overflow = null
                    }
                }
            }
        },

        setup(props, {emit}) {
            const close = () => {
                if (props.closeable) {
                    emit('close')
                }
            }

            const closeOnEscape = (e) => {
                if (e.key === 'Escape' && props.show) {
                    close()
                }
            }

            onMounted(() => document.addEventListener('keydown', closeOnEscape))
            onUnmounted(() => {
                document.removeEventListener('keydown', closeOnEscape)
                document.body.style.overflow = null
            })

            return {
                close,
            }
        },

        computed: {
            maxWidthClass() {
                return {
                    'sm': 'sm:max-w-sm',
                    'md': 'sm:max-w-md',
                    'lg': 'sm:max-w-lg',
                    'xl': 'sm:max-w-xl',
                    '2xl': 'sm:max-w-2xl',
                }[this.maxWidth]
            }
        }
    })
</script>
