<template>

    <div class="min-h-full flex">
        <img :src="$page.props.big_logo" class="w-20 h-20 ml-12 mt-12 absolute rounded-full" />
        <div
            class="flex-1 flex min-h-screen flex-col align-items-center justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24">
            <div class="mx-auto  w-full max-w-sm lg:w-96">

                <div>
                    <div class="text-2xl mb-8 font-bold text-black">
                        <img src="/Svgs/Logos/artwork_logo_big.svg"/>
                    </div>
                    <div class="flex items-center mb-12">
                        <h2 class="mt-6 text-3xl font-lexend font-bold text-primary">{{ $t('Reset Password')}}</h2>
                        <SvgCollection svgName="arrowRight" class="mt-12 ml-2"/>
                    </div>
                </div>

                <div class="mt-8">
                    <div class="mt-6">
                        <jet-validation-errors class="mb-4" />
                        <form @submit.prevent="submit">
                            <div>
                                <jet-label for="email" value="Email" />
                                <jet-input id="email" type="email" class="mt-1 block w-full bg-gray-50" v-model="form.email" readonly required autofocus disabled />
                            </div>

                            <div class="mt-4">
                                <jet-label for="password" value="Password" />
                                <jet-input id="password" type="password" class="mt-1 block w-full" v-model="form.password" required autocomplete="new-password" />
                            </div>
                            <div class="flex items-center justify-end mt-4">
                                <jet-button class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    {{ $t('Reset Password')}}
                                </jet-button>
                            </div>
                        </form>

                    </div>
                </div>
                <div class=" absolute bottom-0 mb-20 text-secondary subpixel-antialiased text-sm tracking-wide">
                    <a v-if="this.$page.props.impressumLink !== ''" target="_blank" :href="this.$page.props.impressumLink">
                        {{$t('Imprint')}}
                    </a>
                    <!-- TODO: Wohin bei Default ? -->
                    <a target="_blank" v-else :href="this.$page.props.impressumLink">
                        {{$t('Imprint')}}
                    </a>
                    |
                    <a target="_blank" v-if="this.$page.props.privacyLink !== ''" :href="this.$page.props.privacyLink">
                        {{$t('Privacy Policy')}}
                    </a>
                    <!-- TODO: Wohin bei Default ? -->
                    <a target="_blank" v-else :href="this.$page.props.privacyLink">
                        {{$t('Privacy Policy')}}
                    </a>
                    |
                    <!-- TODO: Hier noch Link zu Über uns Page -->
                    <a href="">
                        {{$t('About the tool')}}
                    </a>
                </div>
            </div>
        </div>
        <div class="hidden lg:block relative w-0 flex-1">
            <img class="absolute inset-0 h-full w-full object-cover"
                 :src="$page.props.banner"
                 alt=""/>
        </div>
    </div>
</template>

<script>
    import { defineComponent } from 'vue';
    import {Head, Link} from '@inertiajs/vue3';
    import JetAuthenticationCard from '@/Jetstream/AuthenticationCard.vue'
    import JetAuthenticationCardLogo from '@/Jetstream/AuthenticationCardLogo.vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetLabel from '@/Jetstream/Label.vue'
    import JetValidationErrors from '@/Jetstream/ValidationErrors.vue'
    import Permissions from "@/Mixins/Permissions.vue";
    import JetInputError from "@/Jetstream/InputError.vue";
    import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
    import Checkbox from "@/Layouts/Components/Checkbox.vue";

    export default defineComponent({
        mixins: [Permissions],
        components: {
            Checkbox, Link, SvgCollection, JetInputError,
            Head,
            JetAuthenticationCard,
            JetAuthenticationCardLogo,
            JetButton,
            JetInput,
            JetLabel,
            JetValidationErrors
        },

        props: {
            email: String,
            token: String,
        },

        data() {
            return {
                form: this.$inertia.form({
                    token: this.token,
                    email: this.email,
                    password: '',
                })
            }
        },

        methods: {
            submit() {
                this.form.post(this.route('password.update'), {
                    onFinish: () => this.form.reset('password'),
                })
            }
        }
    })
</script>
