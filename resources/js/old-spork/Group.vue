<template>
    <div @contextmenu="openContext">
        <div class="ml-3">
            <p class="text-sm font-medium text-stone-900 dark:text-stone-50">{{ file.name }}</p>
            <p class="text-sm text-stone-500 dark:text-stone-200">{{ file.path }}</p>
            <div class="text-sm text-stone-500 dark:text-stone-200">
                <div v-for="(season, index) in seasons" :key="season">
                    season: {{index}}
                    <div v-for="episode in season">
                        {{episode.name }} {{episode.episode }}
                    </div>
                </div>
            </div>
            <p class="text-sm text-stone-500 dark:text-stone-200">{{ files.length }} files</p>
        </div>

        <div  v-if="contextOpen">
            <div @click="contextOpen = false" class="absolute inset-0 z-0 bg-stone-900/20 cusor-pointer"></div>

            <div class="absolute z-10 mt-2 w-56 overflow-hidden rounded-md shadow-lg bg-white dark:bg-stone-600 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1"
                 :style="'top: '+menuPosition.y+'px; left: '+menuPosition.x+'px;'"
            >
                <div class="flex flex-col" role="none">
                    <div class="flex flex-col">
                        <button class="hover:bg-stone-500 text-left px-4 py-2" @click="$emit('rename', file)">rename</button>
                        <button class="hover:bg-stone-500 text-left px-4 py-2" @click="$emit('regroup', file)">re-group</button>
                        <button class="hover:bg-stone-500 text-left px-4 py-2" @click="$emit('rename-all-files', file)">rename all files</button>
                        <button class="hover:bg-stone-500 text-left px-4 py-2" @click="$emit('remove-text-from-string', file)">remove text from string</button>

                        <button class="hover:bg-stone-500 text-left px-4 py-2" @click="">preview</button>
                        <div v-if="selection" class="pt-3 pb-2 px-2 border-t border-b border-stone-500">Quick actions</div>
                        <button
                            v-if="selection"
                            v-for="option in menuOptions"
                            :key="option"
                            class="hover:bg-stone-500 text-left px-4 py-2"
                            @click="() => { updateMappingFromSelection(option) }"
                        >
                            {{ option.name }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {groupBy} from "lodash";

export default {
    name: "Group",
    props: ['files'],
    data() {
        return {
            contextOpen: false,
            menuPosition: {
                x: 0,
                y: 0
            },
            selection: null,
            menuOptions: {},
        }
    },
    computed: {
        file() {
            return this.files[0];
        },
        seasons() {
            return groupBy(this.files, 'season')
        }
    },
    methods: {
        updateMappingFromSelection(option) {
            const selectedIndex = this.parsedData.indexOf(this.selectedFile);
            if (Array.isArray(this.parsedData[selectedIndex][option.value])) {
                this.parsedData[selectedIndex][option.value] = this.selection;
            } else {
                this.parsedData[selectedIndex][option.value] = this.selection;
            }
        },
        openContext(e, file) {
            e.preventDefault()
            this.selectedFile = file;
            console.log('opne context', e)
            this.contextOpen = true;
            this.menuPosition.x = e.clientX;
            this.menuPosition.y = e.clientY + window.scrollY;
        },
    }
}
</script>

<style scoped>

</style>
