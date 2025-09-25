<template>
    <!-- Toggle -->
    <SwitchGroup as="div" class="flex items-center">
        <Switch
            v-model="userEdit.temporary"
            :class="[
        userEdit.temporary ? 'bg-blue-600' : 'bg-zinc-300',
        'relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2'
      ]"
        >
      <span
          aria-hidden="true"
          :class="[
          userEdit.temporary ? 'translate-x-5' : 'translate-x-0',
          'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
        ]"
      />
        </Switch>
        <SwitchLabel as="span" class="ml-3 text-sm">
            <span class="font-medium text-zinc-900">{{ $t('Temporarily employed') }}</span>
        </SwitchLabel>
    </SwitchGroup>

    <!-- Zeitraum -->
    <div v-if="userEdit.temporary" class="mt-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <BaseInput
                    id="startDate"
                    type="date"
                    v-model="userEdit.employStart"
                    :label="$t('Start date')"
                    :disabled="!userEdit.temporary"
                    required
                    @change="checkChanges"
                />
                <div class="text-xs text-red-600 mt-1" v-show="employStartText.length > 0">{{ employStartText }}</div>
            </div>

            <div>
                <BaseInput
                    id="endDate"
                    type="date"
                    v-model="userEdit.employEnd"
                    :label="$t('End date')"
                    :disabled="!userEdit.temporary"
                    required
                    @change="checkChanges"
                />
                <div class="text-xs text-red-600 mt-1" v-show="employEndText.length > 0">{{ employEndText }}</div>
            </div>
        </div>

        <div v-show="helpText.length > 0" class="text-xs text-red-600 mt-2">
            {{ helpText }}
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Switch, SwitchGroup, SwitchLabel } from '@headlessui/vue'
import { useForm } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    user: { type: Object, required: true },
})

const userEdit = useForm({
    temporary: props.user.temporary,
    employStart: props.user.employStart,
    employEnd: props.user.employEnd,
})

const employStartText = ref('')
const employEndText = ref('')
const helpText = ref('')
const disabled = ref(false)

// Verhalten wie im Original: nur disabled-Flag spiegeln, kein Auto-Patch auf Toggle
watch(
    () => userEdit,
    () => {
        disabled.value = userEdit.temporary
    },
    { deep: true }
)

const updateTemporaryEmploy = () => {
    if (!userEdit.temporary) {
        userEdit.employEnd = null
        userEdit.employStart = null
    }

    if (dayjs(userEdit.employStart) > dayjs(userEdit.employEnd)) {
        helpText.value = $t('Start date must not be after the end date!')
        return
    } else {
        helpText.value = ''
    }

    userEdit.patch(route('update.user.temporary', props.user.id), {
        preserveState: true,
        preserveScroll: true,
    })
}

const checkChanges = () => {
    if (userEdit.temporary) {
        if (userEdit.employStart === null) {
            employStartText.value = $t('Please choose a start date!')
            disabled.value = true
        } else {
            employStartText.value = ''
            disabled.value = false
        }

        if (userEdit.employEnd === null) {
            employEndText.value = $t('Please choose an end date!')
            disabled.value = true
        } else {
            employEndText.value = ''
            disabled.value = false
        }
    }

    if (disabled.value) return
    updateTemporaryEmploy()
}
</script>
