<!-- Simple select / multiselect with optional object options -->
<template>
  <select
    :multiple="multiple"
    :value="normalizedValue"
    @change="onChange"
    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-700 dark:placeholder-stone-300 focus:outline-none focus:ring-slate-500 focus:border-slate-700 sm:text-sm rounded-md text-stone-900 dark:text-stone-100"
  >
    <option v-for="opt in normalizedOptions" :key="opt[valueKey]" :value="opt[valueKey]">
      {{ opt[labelKey] }}
    </option>
  </select>
</template>

<script>
export default {
  props: {
    modelValue: {
      type: [String, Number, Array, null],
      default: null,
    },
    options: {
      type: Array,
      default: () => [],
    },
    multiple: {
      type: Boolean,
      default: false,
    },
    labelKey: {
      type: String,
      default: 'label',
    },
    valueKey: {
      type: String,
      default: 'value',
    },
  },
  emits: ['update:modelValue'],
  computed: {
    normalizedOptions() {
      // Accept array of primitives or objects
      return this.options.map((o) => {
        if (o !== Object(o)) {
          return { [this.valueKey]: o, [this.labelKey]: String(o) };
        }
        return o;
      });
    },
    normalizedValue() {
      if (this.multiple) {
        return Array.isArray(this.modelValue) ? this.modelValue : [];
      }
      return this.modelValue;
    },
  },
  methods: {
    onChange(e) {
      if (this.multiple) {
        const selected = Array.from(e.target.selectedOptions).map((o) =>
          this.castValue(o.value)
        );
        this.$emit('update:modelValue', selected);
      } else {
        this.$emit('update:modelValue', this.castValue(e.target.value));
      }
    },
    castValue(v) {
      // Cast numeric strings back to numbers if the original options use numbers
      const match = this.normalizedOptions.find(
        (o) => String(o[this.valueKey]) === String(v)
      );
      if (match) {
        const original = this.options.find((o) =>
          o !== Object(o)
            ? String(o) === String(v)
            : String(o[this.valueKey]) === String(v)
        );
        if (original !== undefined && original !== Object(original)) {
          // primitive option: try to cast number
          return isNaN(original) ? original : Number(original);
        }
        // object option: return raw value type (number if numeric)
        return isNaN(match[this.valueKey])
          ? match[this.valueKey]
          : Number(match[this.valueKey]);
      }
      return v;
    },
    focus() {
      this.$refs?.input?.focus?.();
    },
  },
};
</script>