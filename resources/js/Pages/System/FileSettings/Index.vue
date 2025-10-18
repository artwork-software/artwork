<template>
  <ToolSettingsHeader :title="$t('File settings')">
    <div v-if="usePage().props.flash.success"
         class="mt-4 w-full font-bold text-sm border-1 border-green-600 rounded bg-green-600 p-2 text-white mb-3">
      {{ usePage().props.flash.success }}
    </div>
    <div v-for="area in areas" :key="area.name" class="mt-8">
      <h3 class="headline3">{{ $t(area.name) }}</h3>
      <div class="mt-4">
        <Listbox as="div">
          <div class="relative mt-2 w-1/2">
            <ListboxButton class="menu-button">
              <span class="block truncate text-left">{{$t('Select file types')}}</span>
              <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <IconChevronDown  stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
            </ListboxButton>

            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
              <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                <ListboxOption as="template" v-for="fileType in imageFileTypes" :key="fileType" :value="fileType" v-slot="{ active, selected }">
                  <li @click="addFileTypeToArea(area, fileType)" :class="[active ? 'bg-artwork-buttons-create text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                    <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ fileType }}</span>
                    <span v-if="selected" :class="[active ? 'text-white' : 'text-artwork-buttons-create', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <IconCheck stroke-width="1.5" class="h-5 w-5" aria-hidden="true" />
                                            </span>
                  </li>
                </ListboxOption>
              </ListboxOptions>
            </transition>
          </div>
        </Listbox>
        <div class="flex">
          <div class="mt-2">
            <SliderInput
                v-model="area.fileSize"
                :min="1"
                :max="150"
                :step="1"
                :property="{area: area, fileSize: area.fileSize}"
                :show-value="true"
                :label="$t('Max file size in MB')"
                :method="handleSlideValueUpdate"
            />
          </div>
        </div>
      </div>
      <div class="flex items-center gap-x-5">
        <div class="flex mt-4">
          <TagComponent v-for="fileType in area.fileTypes"
                        :key="fileType.name"
                        :method="removeFileTypeFromArea"
                        :hide-x="false"
                        :property="{area: area, fileType: fileType, fileSize: area.fileSize}"
                        :displayed-text="fileType.name"
          />
        </div>
      </div>
    </div>
  </ToolSettingsHeader>
</template>

<script setup>
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import {computed, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import {useTranslation} from "@/Composeables/Translation.js";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import {IconCheck, IconChevronDown} from "@tabler/icons-vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import SliderInput from "@/Components/Form/SliderInput.vue";
import debounce from "lodash.debounce";

const $t = useTranslation(),
    props = defineProps({
      areas: {
        type: Object,
        required: true
      },
      imageFileTypes: {
        type: Object,
        required: true
      },
      otherFileTypes: {
        type: Object,
        required: true
      },
    });

const areas = ref(props.areas);

const addFileTypeToArea = (area, fileType) => {
  const targetArea = areas.value.find(a => a.name === area.name);
  if (!targetArea.fileTypes.find((m) => m.name === fileType)) {
    targetArea.fileTypes.push({ name: fileType });
  }
  updateArea(area);
}

const removeFileTypeFromArea = (data) => {
  const targetArea = areas.value.find(area => area.name === data.area.name);
  const fileType = data.fileType;
  targetArea.fileTypes = targetArea.fileTypes.filter((m) => m.name !== fileType.name);
  updateArea(targetArea);
}

const handleSlideValueUpdate = (property, value) => {
  const targetArea = areas.value.find(area => area.name === property.area.name);
  targetArea.fileSize = parseInt(value);
  updateArea(targetArea);
}

const updateArea = debounce((area) => {
  router.put(route('tool.file-settings.store', {}), {
    data: area
  })
}, 500);
</script>
