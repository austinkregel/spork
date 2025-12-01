<script setup>
import { computed } from 'vue';
import SshStepForm from "@/Components/Automation/steps/SshStepForm.vue";
import TaggingStepForm from "@/Components/Automation/steps/TaggingStepForm.vue";
import NotifyStepForm from "@/Components/Automation/steps/NotifyStepForm.vue";
import WaitStepForm from "@/Components/Automation/steps/WaitStepForm.vue";
import ConditionStepForm from "@/Components/Automation/steps/ConditionStepForm.vue";
import HttpStepForm from "@/Components/Automation/steps/HttpStepForm.vue";
import OperationStepForm from "@/Components/Automation/steps/OperationStepForm.vue";

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:modelValue']);

const steps = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
});

const GLOBAL_CONDITION_PARAMETERS = [
  { value: 'time.iso', name: 'Current time (ISO8601)' },
  { value: 'time.timestamp', name: 'Current timestamp' },
  { value: 'date', name: 'Today (Y-m-d)' },
  { value: 'automation.id', name: 'Automation ID' },
  { value: 'automation.name', name: 'Automation name' },
  { value: 'user.id', name: 'User ID' },
  { value: 'user.email', name: 'User email' },
];

const STEP_PARAMETER_PRESETS = {
  ssh: [
    { value: 'stdout', name: 'SSH stdout' },
    { value: 'stderr', name: 'SSH stderr' },
    { value: 'exit_code', name: 'SSH exit code' },
  ],
  notify: [
    { value: 'recipients', name: 'Recipients' },
    { value: 'level', name: 'Level' },
  ],
  wait: [
    { value: 'ms', name: 'Wait milliseconds' },
  ],
  dusk: [
    { value: 'page.title', name: 'Page title' },
    { value: 'page.url', name: 'Page URL' },
  ],
  tagging: [
    { value: 'tag.name', name: 'Tag name' },
    { value: 'tag.type', name: 'Tag type' },
  ],
  condition: [],
  operation: [
    { value: 'operation.operation_id', name: 'Operation ID' },
    { value: 'operation.operation', name: 'Operation class' },
    { value: 'operation.output', name: 'Operation output' },
  ],
};

const typeLabel = (type) => {
  return ({
    ssh: 'SSH',
    notify: 'Notify',
    wait: 'Wait',
    condition: 'Condition',
    tagging: 'Tagging',
    dusk: 'Dusk',
    http: 'HTTP',
    operation: 'Operation',
  })[type] ?? type;
};

const buildConditionSources = (currentIndex) => {
  const sources = [{
    value: 'globals',
    label: 'Global context',
    parameters: GLOBAL_CONDITION_PARAMETERS,
  }];

  steps.value.slice(0, currentIndex).forEach((previousStep, index) => {
    const params = STEP_PARAMETER_PRESETS[previousStep.type] ?? [];
    sources.push({
      value: `step:${index}`,
      label: `Step ${index + 1} (${typeLabel(previousStep.type)})`,
      parameters: params,
    });
  });

  return sources;
};

const addStep = () => {
  const order = (steps.value.at(-1)?.order ?? -1) + 1;
  steps.value = [
    ...steps.value,
    { order, type: 'dusk', config: { action: 'visit', selector: '', value: '', ms: 500 } },
  ];
};

const removeStep = (idx) => {
  const updated = steps.value.slice();
  updated.splice(idx, 1);
  steps.value = updated.map((s, i) => ({ ...s, order: i }));
};
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-medium text-stone-800 dark:text-stone-200">Steps</h3>
      <button type="button" @click="addStep" class="px-3 py-1.5 text-xs rounded-md bg-indigo-500 dark:bg-indigo-600 text-white">
        Add step
      </button>
    </div>

    <div v-if="steps.length === 0" class="text-sm text-stone-600 dark:text-stone-300">
      No steps yet. Add your first step.
    </div>

    <div v-for="(step, idx) in steps" :key="idx" class="border border-stone-200 dark:border-stone-700 rounded-md p-3 space-y-2">
      <div class="flex items-center justify-between">
        <div class="text-sm text-stone-700 dark:text-stone-200">Step {{ idx + 1 }}</div>
        <button type="button" @click="removeStep(idx)" class="text-xs text-red-600">Remove</button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Type</label>
          <select v-model="step.type" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm">
            <option value="dusk">Dusk</option>
            <option value="ssh">SSH</option>
            <option value="tagging">Tagging</option>
            <option value="notify">Notify</option>
            <option value="wait">Wait</option>
            <option value="condition">Condition</option>
            <option value="http">HTTP</option>
            <option value="operation">Operation</option>
          </select>
        </div>
        <div v-if="step.type === 'dusk'">
          <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Action</label>
          <select v-model="step.config.action" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm">
            <option value="visit">visit</option>
            <option value="click">click</option>
            <option value="fill">fill</option>
            <option value="wait">wait</option>
          </select>
        </div>
        <div v-if="step.type === 'dusk' && (step.config.action === 'visit' || step.config.action === 'click' || step.config.action === 'fill')">
          <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Selector / URL</label>
          <input v-model="step.config.selector" placeholder="#id or .class or //xpath or url"
                 class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
        </div>
        <div v-if="step.type === 'dusk' && step.config.action === 'fill'">
          <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Value</label>
          <input v-model="step.config.value" class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
        </div>
        <div v-if="step.type === 'dusk' && step.config.action === 'wait'">
          <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Milliseconds</label>
          <input type="number" v-model.number="step.config.ms" min="0"
                 class="w-full border border-stone-300 dark:border-stone-700 rounded-md bg-white dark:bg-stone-800 px-2 py-1.5 text-sm" />
        </div>
      </div>
      <div v-if="step.type === 'ssh'" class="mt-2">
        <SshStepForm v-model="step.config" />
      </div>
      <div v-if="step.type === 'tagging'" class="mt-2">
        <TaggingStepForm v-model="step.config" />
      </div>
      <div v-if="step.type === 'notify'" class="mt-2">
        <NotifyStepForm v-model="step.config" />
      </div>
      <div v-if="step.type === 'wait'" class="mt-2">
        <WaitStepForm v-model="step.config" />
      </div>
      <div v-if="step.type === 'condition'" class="mt-2">
        <ConditionStepForm v-model="step.config" :sources="buildConditionSources(idx)" />
      </div>
      <div v-if="step.type === 'http'" class="mt-2">
        <HttpStepForm v-model="step.config" />
      </div>
      <div v-if="step.type === 'operation'" class="mt-2">
        <OperationStepForm v-model="step.config" />
      </div>
    </div>
  </div>
</template>


