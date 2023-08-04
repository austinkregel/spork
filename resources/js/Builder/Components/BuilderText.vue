<!-- This example requires Tailwind CSS v2.0+ -->
<template>
  <p
    :class="[size]"
    >
    {{ text }}
  </p>
</template>

<script>
export default {
    props: {
        text: {
            default: 'button'
        },
        small: {
            type: Boolean,
            default: false
        },
        medium: {
            type: Boolean,
            default: false
        },
        large: {
            type: Boolean,
            default: false
        },
        xlarge: {
            type: Boolean,
            default: false
        },
    },
    computed: {
        size() {
            if (this.small) {
                return 'px-3 py-2 text-sm leading-4'
            }
            if (this.large) {
                return 'px-4 py-2 text-base'
            }
            if (this.xlarge) {
                return 'px-4 py-2 text-base'
            }

            return 'px-3 py-2 text-sm'
        },
        iconSize() {

            if (this.small) {
                return '-ml-0.5 mr-2 h-4 w-4'
            }
            if (this.large) {
                return '-ml-1 mr-3 h-5 w-5'
            }
            if (this.xlarge) {
                return '-ml-1 mr-3 h-5 w-5'
            }

            return '-ml-1 mr-2 h-5 w-5'
        }
    }
}
</script>
