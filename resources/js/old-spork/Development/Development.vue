<template>
    <div class="flex flex-wrap h-screen font-mono text-base">
        <div class="bg-stone-900 h-full border-l-2 border-black flex flex-col" :style="collapsed ? 'width: 55px;':'width: 300px;'">

            <div class="flex flex-col gap-1 mt-1 bg-stone-900 shadow">
                <div class="flex justify-between w-full px-4">
                    <router-link to="/dev" class="text-lg  ">Projects</router-link>
                </div>

                <div class="flex flex-col gap-2 max-h-32 overflow-y-scroll  scrollbar scrollbar-thin scrollbar-thumb-stone-800 scrollbar-track-stone-500">
                    <!-- Project List -->
                    <div v-for="item in routes" :key="item.name" >
                        <div class="mx-2 flex flex-col hover:border-l-2 " :class="$store.getters.openProject?.name === item.name ? 'border-l-2 border-stone-300 dark:border-stone-500 text-stone-800 dark:text-stone-200' : 'border-l border-stone-200 dark:border-stone-500'" >
                            <div class="flex justify-between">
                                <button class="flex flex-col" @click="$store.dispatch('openProject', item)">
                                    <span class="ml-2"
                                          :class="$store.getters.openProject?.name === item.name ? 'underline': '' "
                                    >{{ item.name }}</span>
                                    <span class="ml-2 text-xs text-stone-400 dark:text-stone-400">{{ item?.settings?.path }}</span>
                                </button>

                                <button @click="() => $store.dispatch('deleteFeature', item)">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="tabs" class="flex w-full justify-between" :class="{ 'flex-wrap': !collapsed, 'flex-col': collapsed }">
                <div class="flex" :class="{ 'flex-wrap': !collapsed, 'flex-col': collapsed }">
                    <button @click="tab = 'files'" :class="[tab === 'files' ? 'bg-stone-700' : 'bg-stone-900']" class="py-2 px-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                    </button>
                    <button @click="tab = 'settings'" :class="[tab === 'settings' ? 'bg-stone-700' : 'bg-stone-900']" class="py-2 px-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </button>
                </div>
                <button class="p-2 mx-2" @click="() => toggleCollapse()">
                    <svg v-if="!collapsed" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    <svg v-else class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
            <div v-if="!collapsed && ! $store.getters.loadingFiles" class="p-2 bg-stone-700 flex-grow max-h-screen overflow-y-scroll">
                <div v-if="tab === 'files'" id="tab-content">
                    <file-or-folder
                     v-for="file in $store.getters.files"
                     :key="file.absolute"
                     :file="file"
                     @contextmenu.prevent="(e) => openContextMenu(e, file)"
                     openContextMenu
                    ></file-or-folder>

                    <div v-if="!$store.getters.openProject?.settings?.template" class="w-full flex flex-col justify-center items-center">
                        <button class=" my-4 py-1 px-4 border-stone-400 border rounded hover:font-bold hover:tracking-wide">Open A Project</button>
                        <feature-required class="mx-2" feature="development" :allow-more-than-one="true" :settings="{ template: { src: 'https://github.com/spork-app/template-plugin/archive/refs/heads/main.zip'}, use_git: true, path: '/var/www/html' }"/>
                    </div>


                </div>
                <div v-else-if="tab === 'settings'" class="space-y-4">
                    <div>
                        <label class="flex flex-wrap items-center gap-2 -ml-4">
                            <input type="checkbox" v-model="form.use_git">
                            <span class="ml-4">Git init new developments</span>
                        </label>
                    </div>

                    <hr class="border-t dark:border-stone-500 border-stone-300">

                    <div class="flex flex-col gap-2" v-if="$store.getters.openProject">
                        <div class="text-red-500 uppercase my-2 font-bold tracking-wide">Danger Zone</div>

                        <button
                            v-if="$store.getters.openProject?.settings?.template"
                            class="bg-red-500 text-white px-1 rounded-lg border-2 border-red-500"

                            @click="redeploy"
                        >Redeploy Development</button>
                        <button
                            class="bg-red-500 text-white px-1 rounded-lg border-2 border-red-500"
                            @click="destroy"
                        >Delete {{$store.getters.openProject.name }}, and files</button>
                        <button
                            class="bg-red-500 text-white px-1 rounded-lg border-2 border-red-500"
                            @click="destroy"
                        >Delete {{$store.getters.openProject.name }}, keep files</button>
                    </div>
                </div>
            </div>
            <div v-else-if="$store.getters.loadingFiles && !collapsed" class="w-full h-full flex gap-2 items-center justify-center">
                <loading-ascii art="balloon" timeout="175"></loading-ascii>
                <span>Loading files</span>
            </div>

        </div>
        <div class="flex-grow">
            <router-view></router-view>
        </div>

        <div  v-if="openContext && openForFile">
            <div @click="openContext = false" class="absolute inset-0 z-0 bg-stone-900/20 cusor-pointer"></div>

            <div class="absolute z-10 mt-2 w-56 overflow-hidden rounded-md shadow-lg bg-white dark:bg-stone-600 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1"
            :style="'top: '+contextY+'px; left: '+contextX+'px;'"
            >
                <div class="flex flex-col" role="none">
                    <div v-if="openForFile.is_directory" class="flex flex-col">
                        <!-- Directory options???? -->
                    </div>
                    <div v-else class="flex flex-col">
                        <!-- file options???? -->
                    </div>

                    <div class="flex flex-col">
                        <button class="hover:bg-stone-500 text-left px-4 py-2" @click="createDirectory">New Directory</button>
                        <button class="hover:bg-stone-500 text-left px-4 py-2" @click="createFile">New File</button>
                        <button class="hover:bg-stone-500 text-left px-4 py-2" @click="destroy">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import FileOrFolder from './FileOrFolder'

export default {
    components: { Disclosure, DisclosureButton, DisclosurePanel, FileOrFolder },
    computed: {
        routes() {
            return  this.$store.getters.features?.development?.map(list => ({
                id: list.id,
                name: list.name,
                path: list.settings?.path,
                href: '/dev/editor',
                current: this.$store.getters.openProject?.id == list.id,
                settings: list?.settings
            }))
        }
    },
    data() {
        return {
            collapsed: Spork.getLocalStorage('middleThirdCollapsed', false),
            tab: 'files',
            form: {
                use_git: false
            },
            openForFile: null,
            contextX: null,
            contextY: null,
            openContext: false,
        }
    },
   methods: {
        queryDev(se) {
            // This could be any file, and any contents of any files....
        },
        toggleCollapse() {
            this.collapsed = !this.collapsed
            Spork.setLocalStorage('middleThirdCollapsed', this.collapsed)
        },
        openContextMenu(e, file) {
            this.openContext = true;
            this.contextX = e.clientX;
            this.contextY = e.clientY;
            this.openForFile = file;
        },
        closeMenu() {
            this.openContext = false;
            this.openForFile = null;
        },
        async createDirectory() {
            const name = prompt('Enter a name for the new directory');

            if (name) {
                await this.$store.dispatch('createDirectory', {
                    name
                })
            }
            this.closeMenu()

        },
        async createFile() {
            const name = prompt('Enter a name for the new file');

            if (name) {
                await this.$store.dispatch('createFile', {
                    name
                })
            }
            this.closeMenu()
        },
        async destroy() {
            if (!confirm('Are you sure you want to delete this project? This action is perminent.')) {
                return;
            }
            await this.$store.dispatch('destroyFileOrDirectory', this.openForFile);

            this.closeMenu()
        },

        async redeploy () {
            if (!confirm('Are you sure you want to redeploy? This action is perminent.')) {
                return;
            }

            await this.$store.dispatch('redeploy')
        }
    },
    mounted() {

        this.$store.dispatch('fetchFeatures', {
            page: 1,
            limit: 100,
            feature: 'development',
        });
        // So for some reason this shit isn't working...
        Echo.join('user.'+this.$store.getters.isAuthenticated.id)
                .here((users) => {
                    console.log(' here', { users})
                })
                .joining((user) => {
                    console.log('joining', user);
                })
                .leaving((user) => {
                    console.log('leaving', user);
                })
                .error((error) => {
                    console.error(error);
                })
                .listen('.PublishGitInformationRequested', (event) => {
                    console.log('PublishGitInformationRequested', event);
                })
                .listen('GitInformationRetrievedBroadcast', (event) => {
                    console.log('GitInformationRetrievedBroadcast', event);
                })
                .listen('.GitInformationRetrievedBroadcast', (event) => {
                    console.log('GitInformationRetrievedBroadcast', event);
                })
    }
}
</script>
