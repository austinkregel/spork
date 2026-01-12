<script setup>
import axios from "axios";
import { computed, ref, watch } from "vue";

const { conditions, id, type, parameterGroups } = defineProps({
  conditions: {
    type: Array,
    default: () => [],
  },
  id: {
    type: Number,
    required: true,
  },
  type: {
    type: String,
    required: true,
  },
  parameterGroups: {
    type: Array,
    default: () => [],
  },
});

const editableConditions = ref([]);
const rowErrors = ref({});
const rowSaving = ref({});

watch(
  () => conditions,
  (val) => {
    editableConditions.value = Array.isArray(val) ? val.map((c) => ({ ...c })) : [];
  },
  { immediate: true }
);

const isTagConditionsEditor = computed(() => {
  return String(type ?? "") === "App\\Models\\Tag";
});

const fallbackParameterGroups = computed(() => ([
  {
    label: "Transaction",
    options: [
      { value: "transaction.name", name: "Name" },
      { value: "transaction.amount", name: "Amount" },
      { value: "transaction.personal_finance_category", name: "Personal finance category" },
      { value: "transaction.date", name: "Date" },
    ],
  },
  {
    label: "Email",
    options: [
      { value: "email.from_email", name: "From email" },
      { value: "email.subject", name: "Subject" },
    ],
  },
]));

const effectiveParameterGroups = computed(() => {
  return Array.isArray(parameterGroups) && parameterGroups.length > 0
    ? parameterGroups
    : fallbackParameterGroups.value;
});

const flatParameters = computed(() => effectiveParameterGroups.value.flatMap((g) => g.options ?? []));
const knownParameterValues = computed(() => new Set(flatParameters.value.map((p) => p.value)));

const comparators = [
  { value: "EQUALS", name: "equal to" },
  { value: "NOT_EQUAL", name: "not equal to" },
  { value: "LIKE", name: "like" },
  { value: "LIKE_STRICT", name: "like (strict)" },
  { value: "NOTLIKE", name: "not like" },
  { value: "IN", name: "in" },
  { value: "NOTIN", name: "not in" },
  { value: "STARTS_WITH", name: "starts with" },
  { value: "ENDS_WITH", name: "ends with" },
  { value: "LESS_THAN", name: "less than" },
  { value: "LESS_THAN_EQUAL", name: "less than or equal" },
  { value: "GREATER_THAN", name: "greater than" },
  { value: "GREATER_THAN_EQUAL", name: "greater than or equal" },
];

const newConditionDefaults = () => {
  const fallbackParam = flatParameters.value[0]?.value ?? "transaction.name";

  return {
    parameter: fallbackParam,
    comparator: "LIKE",
    value: "",
  };
};

const addCondition = () => {
  editableConditions.value = [...editableConditions.value, newConditionDefaults()];
};

const apiForCreate = () => {
  if (isTagConditionsEditor.value) {
    return route("automation.tags.conditions.store", id);
  }

  return "/api/crud/conditions";
};

const apiForUpdate = (conditionId) => {
  if (isTagConditionsEditor.value) {
    return route("automation.tags.conditions.update", { tag: id, condition: conditionId });
  }

  return `/api/crud/conditions/${conditionId}`;
};

const apiForDelete = (conditionId) => {
  if (isTagConditionsEditor.value) {
    return route("automation.tags.conditions.destroy", { tag: id, condition: conditionId });
  }

  return `/api/crud/conditions/${conditionId}`;
};

const createCondition = async (index, condition) => {
  rowSaving.value[index] = true;
  rowErrors.value[index] = null;

  try {
    const payload = isTagConditionsEditor.value
      ? { parameter: condition.parameter, comparator: condition.comparator, value: condition.value }
      : {
          parameter: condition.parameter,
          comparator: condition.comparator,
          value: condition.value,
          conditionable_id: id,
          conditionable_type: type,
        };

    const response = await axios.post(apiForCreate(), payload);
    editableConditions.value[index] = response.data;
  } catch (e) {
    rowErrors.value[index] = e?.response?.data ?? { message: "Failed to create condition." };
  } finally {
    rowSaving.value[index] = false;
  }
};

const updateCondition = async (index, condition) => {
  if (!condition?.id) {
    return;
  }

  rowSaving.value[index] = true;
  rowErrors.value[index] = null;

  try {
    const payload = { parameter: condition.parameter, comparator: condition.comparator, value: condition.value };
    const response = await axios.put(apiForUpdate(condition.id), payload);
    editableConditions.value[index] = response.data;
  } catch (e) {
    rowErrors.value[index] = e?.response?.data ?? { message: "Failed to update condition." };
  } finally {
    rowSaving.value[index] = false;
  }
};

const deleteCondition = async (index, condition) => {
  if (!condition?.id) {
    editableConditions.value = editableConditions.value.filter((_, i) => i !== index);
    return;
  }

  rowSaving.value[index] = true;
  rowErrors.value[index] = null;

  try {
    await axios.delete(apiForDelete(condition.id));
    editableConditions.value = editableConditions.value.filter((_, i) => i !== index);
  } catch (e) {
    rowErrors.value[index] = e?.response?.data ?? { message: "Failed to delete condition." };
  } finally {
    rowSaving.value[index] = false;
  }
};
</script>

<template>
  <div class="p-4 space-y-4">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-sm font-semibold text-stone-900 dark:text-white">Conditions</div>
        <div class="text-xs text-stone-500 dark:text-stone-400">
          Each row describes a match rule. Use <span class="font-medium">in</span> with comma-separated values.
        </div>
      </div>
      <button
        type="button"
        class="px-3 py-2 text-sm rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
        @click="addCondition"
      >
        Add condition
      </button>
    </div>

    <div class="space-y-3">
      <div
        v-for="(condition, index) in editableConditions"
        :key="condition?.id ?? `new-${index}`"
        class="rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-3"
      >
        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start">
          <div class="md:col-span-5">
            <label class="block text-xs text-stone-600 dark:text-stone-400 mb-1">Parameter</label>
            <select
              v-model="condition.parameter"
              class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-sm text-stone-900 dark:text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
            >
              <option value="" disabled>Select a parameter…</option>
              <option
                v-if="condition.parameter && !knownParameterValues.has(condition.parameter)"
                :value="condition.parameter"
              >
                {{ condition.parameter }}
              </option>
              <template v-for="group in effectiveParameterGroups" :key="group.label">
                <optgroup :label="group.label">
                  <option v-for="param in group.options" :key="param.value" :value="param.value">
                    {{ param.name }}
                  </option>
                </optgroup>
              </template>
            </select>
          </div>

          <div class="md:col-span-3">
            <label class="block text-xs text-stone-600 dark:text-stone-400 mb-1">Comparator</label>
            <select
              v-model="condition.comparator"
              class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-sm text-stone-900 dark:text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
            >
              <option v-for="comp in comparators" :key="comp.value" :value="comp.value">{{ comp.name }}</option>
            </select>
          </div>

          <div class="md:col-span-4">
            <label class="block text-xs text-stone-600 dark:text-stone-400 mb-1">Value</label>
            <input
              v-model="condition.value"
              type="text"
              class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-sm text-stone-900 dark:text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
            />
          </div>
        </div>

        <div class="mt-3 flex items-center justify-between gap-2">
          <div class="text-xs text-red-500 dark:text-red-400">
            <span v-if="rowErrors?.[index]?.message">{{ rowErrors[index].message }}</span>
            <span v-else-if="rowErrors?.[index]?.errors">{{ Object.values(rowErrors[index].errors).flat().join(' ') }}</span>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-3 py-2 text-sm rounded-lg border border-stone-300 dark:border-stone-700 text-stone-700 dark:text-stone-200 bg-white dark:bg-stone-900 disabled:opacity-50"
              :disabled="rowSaving?.[index]"
              @click="deleteCondition(index, condition)"
            >
              Remove
            </button>

            <button
              v-if="condition?.id"
              type="button"
              class="px-3 py-2 text-sm rounded-lg bg-indigo-500 dark:bg-indigo-600 text-white shadow-sm disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
              :disabled="rowSaving?.[index]"
              @click="updateCondition(index, condition)"
            >
              Save
            </button>

            <button
              v-else
              type="button"
              class="px-3 py-2 text-sm rounded-lg bg-indigo-500 dark:bg-indigo-600 text-white shadow-sm disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
              :disabled="rowSaving?.[index]"
              @click="createCondition(index, condition)"
            >
              Create
            </button>
          </div>
        </div>
      </div>

      <div v-if="editableConditions.length === 0" class="text-sm text-stone-500 dark:text-stone-400">
        No conditions yet.
      </div>
    </div>
  </div>
</template>

