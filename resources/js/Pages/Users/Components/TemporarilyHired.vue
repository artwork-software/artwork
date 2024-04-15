<template>
    <SwitchGroup as="div" class="flex items-center">
        <Switch v-model="userEdit.temporary" :class="[userEdit.temporary ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
            <span aria-hidden="true" :class="[userEdit.temporary ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
        </Switch>
        <SwitchLabel as="span" class="ml-3 text-sm">
            <span class="font-medium text-gray-900">{{ $t('Temporarily employed')}}</span>
        </SwitchLabel>
    </SwitchGroup>

    <div v-if="userEdit.temporary" class="mt-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <div class="w-full flex">
                    <input v-model="userEdit.employStart"
                           id="startDate"
                           type="date"
                           required
                           @change="checkChanges"
                           class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
                </div>
                <div class="text-xs text-red-500" v-show="employStartText.length > 0">{{ employStartText }}</div>
            </div>
            <div>
                <div class="w-full flex">
                    <input v-model="userEdit.employEnd"
                           id="endDate"
                           type="date"
                           required
                           @change="checkChanges"
                           class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none flex-grow"/>
                </div>
                <div class="text-xs text-red-500" v-show="employEndText.length > 0">{{ employEndText }}</div>
            </div>
        </div>
        <div v-show="helpText.length > 0" class="text-xs text-red-500">
            {{ helpText }}
        </div>
    </div>
</template>
<script>
import {defineComponent} from 'vue'
import { Switch, SwitchGroup, SwitchLabel } from '@headlessui/vue'
import {useForm} from "@inertiajs/inertia-vue3";
import dayjs from "dayjs";
export default defineComponent({
    name: "TemporarilyHired",
    components: {
        Switch,
        SwitchGroup,
        SwitchLabel
    },
    props: ['user'],
    data(){
        return {
            userEdit: useForm({
                temporary: this.user.temporary,
                employStart: this.user.employStart,
                employEnd: this.user.employEnd
            }),
            employStartText: '',
            employEndText: '',
            helpText: '',
            disabled: false
        }
    },
    watch: {
        userEdit: {
            handler(){
                this.disabled = this.userEdit.temporary;
            },
            deep: true
        }
    },
    methods: {
        updateTemporaryEmploy(){
            if (!this.userEdit.temporary) {
                this.userEdit.employEnd = null;
                this.userEdit.employStart = null;
            }

            if (dayjs(this.userEdit.employStart) > dayjs(this.userEdit.employEnd)) {
                this.helpText = this.$t('Start date must not be after the end date!')
                return
            } else {
                this.helpText = '';
            }

            this.userEdit.patch(route('update.user.temporary', this.user.id), {
                preserveState: true, preserveScroll: true
            })
        },
        checkChanges() {
            if (this.userEdit.temporary) {
                if (this.userEdit.employStart === null) {
                    this.employStartText = this.$t('Please choose a start date!')
                    this.disabled = true
                } else {
                    this.employStartText = ''
                    this.disabled = false
                }

                if (this.userEdit.employEnd === null) {
                    this.employEndText = this.$t('Please choose an end date!')
                    this.disabled = true
                } else {
                    this.employEndText = ''
                    this.disabled = false
                }
            }

            if (this.disabled) {
                return;
            }

            this.updateTemporaryEmploy();
        }
    }
})
</script>
<style scoped>

</style>
