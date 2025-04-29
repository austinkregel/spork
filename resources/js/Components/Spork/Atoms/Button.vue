<script setup>
import { buttonProps, baseClasses } from '@/Config/buttonConfig';
import { getButtonColors, getButtonSize, getIconSize } from '@/Utils/buttonStyles';
import  * as allIcons from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const buttonProps = {
  'icon': {
    type: String,
    default: null,
  },
  'xsmall': {
    type: Boolean,
    default: false,
  },
  'small': {
    type: Boolean,
    default: false,
  },
  'medium': {
    type: Boolean,
    default: false,
  },
  'large': {
    type: Boolean,
    default: false,
  },
  'xlarge': {
    type: Boolean,
    default: false,
  },
  'primary': {
    type: Boolean,
    default: false,
  },
  'secondary': {
    type: Boolean,
    default: false,
  },
  'plain': {
    type: Boolean,
    default: false,
  },
  'danger': {
    type: Boolean,
    default: false,
  },
  invert: {
    type: Boolean,
    default: false,
  },
  uppercase: {
    type: Boolean,
    default: false,
  },
  lowercase: {
    type: Boolean,
    default: false
  },
  capitalize: {
    type: Boolean,
    default: false
  },
  normalCase: {
    type: Boolean,
    default: true
  },
};
const props = defineProps(buttonProps);
const emit = defineEmits(['click']);
const buttonStyleConfig = {
  plain: {
    normal: [
      'text-black',
      'dark:text-slate-50',
      'border-slate-200',
      'dark:border-slate-600',
      'hover:opacity-50',
      'dark:focus:ring-offset-indigo-800',
      'focus:ring-indigo-500',
      'target:ring-indigo-600',
      'focus:dark:ring-indigo-600',
      'target:dark:ring-indigo-600',
    ],
  },
  primary: {
    inverted: [
      'text-indigo-500',
      'dark:text-indigo-300',
      'border-indigo-500',
      'dark:border-indigo-300',

      'hover:bg-indigo-50',
      'hover:dark:bg-indigo-950',

      'hover:border-indigo-600',
      'hover:dark:border-indigo-400',
      'hover:shadow-sm',

      'hover:text-indigo-600',
      'hover:dark:text-indigo-400',

      'focus:ring-indigo-500',
      'focus:ring-offset-2',
      'dark:focus:ring-offset-indigo-800',
      'focus:dark:ring-indigo-600',
    ],
    normal: [
      'bg-indigo-500',
      'border-transparent',
      'dark:bg-indigo-600',
      'dark:border-indigo-600',
      'text-white',
      'dark:text-indigo-50',
      'hover:bg-indigo-700',
      'dark:hover:bg-indigo-700',
      'focus:bg-indigo-700',
      'dark:focus:bg-indigo-900',
      'active:bg-indigo-900',
      'dark:active:bg-indigo-800',
      'focus:ring-indigo-500',
      'focus:dark:ring-indigo-500',
      'focus:ring-offset-2',
      'dark:focus:ring-offset-indigo-800',
    ]
  },
  secondary: {
    inverted: [
      'border',
      'border-slate-300',
      'dark:border-slate-500',
      'text-slate-700',
      'dark:text-slate-300',
      'hover:bg-slate-50',
      'dark:hover:bg-slate-700',
      'focus:ring-indigo-500',
      'dark:focus:ring-offset-slate-800',
      'shadow-sm',
    ],
    normal: [
      'bg-slate-400',
      'dark:bg-slate-700',
      'border',
      'border-slate-300',
      'dark:border-slate-500',
      'text-white',
      'dark:text-slate-300',
      'hover:bg-slate-500',
      'dark:hover:bg-slate-800',
      'focus:ring-indigo-500',
      'dark:focus:ring-offset-slate-800',
      'shadow-sm'
    ]
  },
  danger: {
    inverted: [
      'border-red-500',
      'dark:border-red-600',
      'text-red-500',
      'dark:text-red-600',
      'hover:dark:text-red-700',
      'hover:border-red-600',
      'hover:dark:border-red-700',
      'focus:ring-red-600',
      'shadow-sm'
    ],
    normal: [
      'bg-red-500',
      'hover:bg-red-600',
      'dark:bg-red-600',
      'focus:ring-red-500',
      'active:bg-red-800',
      'text-red-50',
      'dark:text-white',
      'dark:hover:bg-red-700',
      'dark:focus:bg-red-800',
      'dark:border-red-500',
      'dark:focus:ring-slate-800',
      'shadow-sm'
    ]
  },
};

const sizeConfig = {
  xsmall: 'px-2 py-1 text-xs leading-4',
  small: 'px-2.5 py-1.5 text-sm leading-4',
  default: 'px-4 py-2 text-sm',
  large: 'px-5 py-2.5 text-sm',
  xlarge: 'px-6 py-3 text-sm',
};

const iconSizeConfig = {
  xsmall: 'h-4 w-4',
  small: 'h-4 w-4',
  large: 'h-5 w-5',
  xlarge: 'h-5 w-5',
  default: 'h-5 w-5'
};
export const baseClasses = (props) => [
  'inline-flex',
  'gap-2',
  'border',
  'items-center',
  'font-semibold',
  'rounded-md',
  'focus:outline-none',
  'cursor-pointer',
  'disabled:cursor-not-allowed',
  'disabled:opacity-50',
  'focus:outline-hidden',
  'focus:ring-2',
  'focus:ring-offset-2',
  'transition',
  'ease-in-out',
  'duration-150',
  ...(props.uppercase ? ['uppercase tracking-wider'] : []),
  ...(props.lowercase ? ['lowercase tracking-wider'] : []),
  ...(props['normal-case'] ? ['normal-case tracking-wider'] : []),
  ...(props.capitalize ? ['capitalize tracking-wider'] : []),
];
function getButtonColors(props) {
  const variant = ['primary', 'secondary', 'danger', 'plain'].find(v => props[v]);
  if (!variant) {
    return [
      'border-transparent p-0',
      'target:outline-none',
      'outline-none',
      'active:ring-indigo-900',
      'target:ring-indigo-900',
      'active:border-indigo-400',
      'focus:dark:ring-indigo-600',
      'target:dark:ring-indigo-600',
    ];
  }

  const style = props.invert ? 'inverted' : 'normal';
  return buttonStyleConfig[variant][style];
}

function getButtonSize(props) {
  const variant = ['primary', 'secondary', 'danger', 'plain'].find(v => props[v]);

  if (!variant) {
    // We don't want to set a button size if there is no variant
    return [];
  }

  const size = ['xsmall', 'small', 'large', 'xlarge'].find(s => props[s]);
  return sizeConfig[size || 'default'];
}

function getIconSize(props) {
  const size = ['xsmall', 'small', 'large', 'xlarge'].find(s => props[s]);
  return iconSizeConfig[size || 'default'];
}
const colors = computed(() => getButtonColors(props));
const size = computed(() => getButtonSize(props));
const iconSize = computed(() => getIconSize(props));

</script>

<template>
  <button
      type="button"
      :class="[...baseClasses(props), colors, size]"
      @click="event => emit('click', event)"
  >
    <component v-if="icon" :is="allIcons[icon]" :class="iconSize" aria-hidden="true"></component>
    <slot></slot>
  </button>
</template>