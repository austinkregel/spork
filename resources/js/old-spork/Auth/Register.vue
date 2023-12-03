<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-800">
        <div>
            <a href="/">
                <img src="/logo_transparent.png" alt="logo" class="h-64">
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-700 shadow-md overflow-hidden sm:rounded-lg">
            <ValidationErrors :errors="$store.state.Authentication.errors"></ValidationErrors>
            <div class="mt-4"></div>
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2" for="name">
                        Name
                    </label>
                    <input class="dark:placeholder-gray-300 dark:text-gray-100 dark:bg-gray-600 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" type="name" placeholder="Name" v-model="form.name">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2" for="email">
                        Email
                    </label>
                    <input class="dark:placeholder-gray-300 dark:text-gray-100 dark:bg-gray-600 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" placeholder="Email" v-model="form.email">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <input class="dark:placeholder-gray-300 dark:text-gray-100 dark:bg-gray-600 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="password" type="password" placeholder="******************" v-model="form.password">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2" for="password">
                        Password Confirmation
                    </label>
                    <input class="dark:placeholder-gray-300 dark:text-gray-100 dark:bg-gray-600 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="password" type="password" placeholder="******************" v-model="form.password_confirmation">
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 dark:bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" :class="[$store.state.Authentication.loading ? 'opacity-75 cursor-not-allowed': '']" type="submit" :disabled="$store.state.Authentication.loading">
                        <span>Log<span v-if="$store.state.Authentication.loading">ging</span> In</span>
                    </button>
                    <router-link to="/forget-password" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                        Forgot Password?
                    </router-link>
                </div>
            </form>
        </div>

        <div class="w-full flex items-center justify-center mt-6">
            <router-link to="/login" class="text-sm text-blue-500 hover:text-blue-800 font-bold dark:text-blue-400 dark:hover:text-blue-300">
                Already have an account?
            </router-link>
        </div>
    </div>
</template> 

<script>
import ValidationErrors from '@components/ValidationErrors.vue';

export default {
    components: {
        ValidationErrors,
    },
    data() {
        return {
            form: {
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
            },
        }
    },
    methods: {
        submit() {
            this.$store.dispatch('register', this.form)
        }
    },
    watch: {
        '$store.getters.isAuthenticated': function(old, newVal) {
            if (newVal) {
                this.$router.push('/planning');
            }
        }
    }
}
</script>