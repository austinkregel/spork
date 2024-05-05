<script setup>
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    TagIcon,
    ServerIcon,
    LinkIcon,
    UserIcon,
    BuildingLibraryIcon,
    WalletIcon
} from "@heroicons/vue/24/outline";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";
import SporkSelect from '@/Components/Spork/SporkSelect.vue';
import SporkInput from "@/Components/Spork/SporkInput.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import { router } from '@inertiajs/vue3';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
const page = usePage();
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'
const variablesOfConstructor = (params) => params.map((e, index) => Object.keys(e)[0])
const variable = (params) => Object.keys(params)[0]
const types = (params) => params[Object.keys(params)[0]]
const addListenerForEvent = async ({event}, listener) => {
    await axios.post('/api/logic/add-listener-for-event', { event, listener: 'App\\Listeners\\DebugEventListener' })
    setTimeout(() => {
        router.reload({
            only: ['events']
        })
    }, 1000)

};
const removeListenerForEvent = async ({ event }, listener) => {
    await axios.post('/api/logic/remove-listener-for-event', { event, listener })
    setTimeout(() => {
        router.reload({
            only: ['events']
        })
    }, 1000)
};

const changeBinding = async (binding, face) => {
    await axios.post('/api/logic/change-binding', { binding, interface: face })
    setTimeout(() => {
        router.reload({
            only: ['container_bindings']
        })
    }, 1000)
};

</script>

<template>
    <AppLayout title="Profile">
        <div>
            <!-- <div class="text-2xl my-8 mx-4">Container Bindings</div>
            <div class="grid grid-cols-2 mx-4">
                <div class="text-stone-400">
                    Change your container's bindings. Once saved, requests made after will use the new binding.
                </div>

                <div class="flex flex-col gap-4 mx-4">
                    <div v-for="(p, face) in page.props.container_bindings" class="bg-stone-800 p-4 rounded-xl gap-2 flex-col flex">
                        <span class="text-purple-400">{{ face.replace('App\\', '') }}<span class="text-stone-400">::</span><span class="text-orange-400">class</span></span>

                        <SporkSelect
                            v-model="p.concrete"
                        >
                            <template v-for="binding in p.instances" :key="binding">
                                <option :value="binding">{{ binding.replace('App\\', '') }}</option>
                            </template>
                        </SporkSelect>
                    </div>
                </div>
            </div> -->

            <div class="text-2xl my-8 mx-4">Ways to handle events in your app</div>

          <div class="gap-8 grid grid-cols-2 mx-auto max-w-7xl px-4">
            <div v-for="(event,) in page.props.events" class="border border-stone-700 p-2 rounded flex flex-col justify-between">
                <div class="flex flex-col gap-2">
                    <div class=" font-bold tracking-wider mx-4">{{ event.event }}</div>

                    <div v-if="event.constructor.length > 0"  class="grid grid-cols-1 mx-4 gap">
                        <div class="text-sm uppercase font-semibold text-stone-400 mb-1">Constructor</div>
                        <div v-for="key in event.constructor" class="flex gap-2 items-center">
                            <div class="w-1/3 text-xs text-stone-400">
                                <div v-for="type in types(key)">{{ type }}</div>
                            </div>
                            <div class="w-1/2 text-blue-400 font-bold">
                                ${{variable(key)}}
                            </div>
                        </div>

                    </div>
                  <div v-else class="bg-stone-800 px-2 py-1 rounded text-stone-500 text-xs italic mx-4">
                    Nothing is in the constructor
                  </div>

                    <div class="mx-4">
                        <div v-for="(methodText, method) in event.methods" class="mt-1 flex flex-col border border-stone-600 p-1 rounded">
                            <pre class="text-orange-400 font-mono">{{ method }}</pre>
                            <SporkInput v-if="false" class="h-20 text-gray-50 font-mono text-xs" v-model="methodText.body" type="textarea"/>
                        </div>
                    </div>

                    <div class="mx-4 mb-4">
                        <div class="overflow-hidden rounded divide-y divide-stone-700">
                            <div class="font-mono flex justify-between text-xs bg-stone-800  py-1 px-2" v-for="listener in event.listeners">
                                <span class="text-purple-400">{{ listener }}<span class="text-stone-400">::</span><span class="text-orange-400">class</span></span>
                                <button class="border border-stone-700 px-1.5 rounded" @click="() => removeListenerForEvent(event, listener)">-</button>
                            </div>
                        </div>

                        <div v-if="event.listeners.length === 0" class="bg-stone-800 text-stone-500 px-2 py-1 rounded text-xs italic">
                            No listeners are defined for this event
                        </div>

                        <div class="relative flex flex-wrap justify-end py-2 text-xs mx-2">
                            <div class="absolute top-0 right-0 mt-2">
                                <Menu as="div" class="relative inline-block text-left">
                                    <div>
                                        <MenuButton class="border border-stone-700 px-1.5 rounded">
                                            +
                                        </MenuButton>
                                    </div>

                                    <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                        <MenuItems class="absolute right-0 z-10 mt-2 origin-top-right rounded-md bg-white dark:bg-stone-700 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                            <div class="py-1">
                                                <MenuItem v-for="listener in page.props.listeners" v-slot="{ active }">
                                                    <button @click="() => addListenerForEvent(event, listener)" :class="['hover:dark:bg-stone-800 text-gray-700 dark:text-stone-50', 'block px-4 py-2 text-sm']">
                                                        <span class="text-purple-400">{{ listener.replace('App\\', '') }}<span class="text-stone-400">::</span><span class="text-orange-400">class</span></span>
                                                    </button>
                                                </MenuItem>
                                            </div>
                                        </MenuItems>
                                    </transition>
                                </Menu>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
