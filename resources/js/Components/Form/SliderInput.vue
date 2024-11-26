<template>
  <div class="w-full flex flex-col items-center">
    <label class="text-gray-700 text-sm mb-2" v-if="label">{{ label }}</label>

    <div class="relative w-full flex items-center">
      <input
          type="range"
          :min="min"
          :max="max"
          :step="step"
          v-model="value"
          class="appearance-none w-full h-2 rounded-full bg-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 slider-thumb"
      />
      <div class="ml-4 text-gray-700 text-sm font-medium w-10 text-center">
        {{ value }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SliderInput",
  props: {
    modelValue: {
      type: Number,
      required: true,
    },
    min: {
      type: Number,
      default: 0,
    },
    max: {
      type: Number,
      default: 100,
    },
    step: {
      type: Number,
      default: 1,
    },
    label: {
      type: String,
      default: "",
    },
    showValue: {
      type: Boolean,
      default: true,
    },
  },
  emits: ["update:modelValue"],
  computed: {
    value: {
      get() {
        return this.modelValue;
      },
      set(val) {
        this.$emit("update:modelValue", val);
      },
    },
  },
};
</script>

<style>
input[type="range"]::-webkit-slider-thumb {
  appearance: none;
  width: 16px;
  height: 16px;
  background-color: #3b82f6;
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 0 3px rgba(0, 0, 0, 0.2);
}
input[type="range"]::-moz-range-thumb {
  width: 16px;
  height: 16px;
  background-color: #3b82f6;
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid white;
}
input[type="range"]::-ms-thumb {
  width: 16px;
  height: 16px;
  background-color: #3b82f6;
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid white;
}
</style>