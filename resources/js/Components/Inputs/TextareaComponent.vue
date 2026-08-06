<template>
    <div class="flex flex-col relative w-full" :class="noMarginTop || (showLabel && hasLabel) ? '' : 'mt-5'">
        <label v-if="showLabel && hasLabel"
               :for="this.id"
               class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">
            {{ this.label }}<span v-if="required" class="text-danger"> *</span>
        </label>
        <textarea :id="this.id"
                  :value="this.modelValue"
                  @input="this.$emit('update:modelValue', $event.target.value)"
                  :required="required"
                  class="input py-2"
                  :rows="this.rows"
                  :cols="this.cols"
                  :maxlength="maxLength"
                  v-bind="$attrs"
        />
    </div>
</template>

<script>
import {defineComponent} from "vue";

export default defineComponent({
    props: {
        id: {
            type: String,
            required: true
        },
        label: {
            type: String,
            default: '',
            required: false
        },
        modelValue: {
            type: [String, null],
            required: true
        },
        isSmall: {
            type: Boolean,
            default: false
        },
        required: {
            type: Boolean,
            default: false
        },
        rows: {
            type: [Number, String],
            default: 3
        },
        cols: {
            type: Number,
            default: 30
        },
        isDark: {
            type: Boolean,
            default: false
        },
        maxLength: {
            type: Number,
            default: 255,
            required: false
        },
        showLabel: {
            type: Boolean,
            default: true
        },
        noMarginTop: {
            type: Boolean,
            default: false
        }
    },
    emits: [
        'update:modelValue'
    ],
    computed: {
        hasLabel() {
            return typeof this.label === 'string' && this.label.trim().length > 0;
        }
    }
});
</script>
