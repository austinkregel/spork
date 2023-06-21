
<template>
    <button @click="execAction" :class="'flex items-center focus:outline-none ' + buttonClasses">
        <svg v-if="!loading" :class="classes" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3"></path>
        </svg>
        <svg v-else fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3"></path>
        </svg>        <slot></slot>
    </button>
</template>

<script>
export default {
    props: {
        action: {
            type: Function,
            default: () => {
                console.error('You must pass an :action prop to the action button.')
            }
        },
        icon: {
            type: String,
            default: 'close-outline'
        },
        classes: {
            type: String,
            default: 'text-red fill-current w-4 h-4 mr-2'
        },
        buttonClasses: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            loading: false,
        }
    },
    methods: {
        execAction() {
            this.loading = true;
            let response = this.action()
            if (!response instanceof Promise) {
                console.error('You must return a promise on your action passed to your action button!')
            }

            response.then(() => {
                this.loading = false
            }).catch(() => {
                this.loading = false
            });
        }
    },
    mounted() {

    }
}
</script>
