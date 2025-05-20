<template>
    <div>
        <div class="pl-2 flex flex-col w-full"
            :class="[
                ['app', 'src', 'resources', 'system'].includes(name) ? 'bg-blue-500/10' :'',
                ['bootstrap', 'public'].includes(name) ? 'bg-stone-700/10' :'',
                ['vendor', 'node_modules'].includes(name) ? 'bg-orange-500/10' :'',
            ]">
            <button
                v-if="folder"
                class="flex gap-1 items-center dark:hover:bg-stone-900/50 focus:bg-stone-900/50 focus:ring-2 focus:ring-blue-800 text-stone-700 dark:text-stone-200"
                :class="[
                    ['tests'].includes(name) ? 'text-green-600 dark:text-green-400' :'',
                    ['app', 'src', 'resources', 'system'].includes(name) ? 'text-blue-500 dark:text-blue-300' :'',
                    ['bootstrap', 'public'].includes(name) ? 'text-stone-600 dark:text-stone-400' :'',
                    ['vendor', 'node_modules'].includes(name) ? 'text-orange-600 dark:text-orange-400' :'',
                    // Projects in an existing file root.
                ]"
                @dblclick="onToggleFolderExpand"
            >
                <ArrowPathIcon v-if="loading" slot="icon" class="w-4 h-4 animate-spin"></ArrowPathIcon>
                <ChevronRightIcon v-else-if="!open" slot="icon" class="w-4 h-4 flex-none" />
                <ChevronDownIcon v-else class="w-4 h-4 flex-none" />

                <FolderIcon slot="icon" v-if="!open" class="w-4 h-4 flex-none"></FolderIcon>
                <FolderOpenIcon slot="icon" v-else class="w-4 h-4 flex-none"></FolderOpenIcon>

                <span class="tracking-tight">{{ name }}</span>
            </button>
            <button v-else class="flex gap-1 items-center  dark:hover:bg-stone-900/50 focus:bg-stone-900/50  focus:ring-2 focus:ring-blue-800 text-nowrap"
                :class="[
                    ['artisan', 'composer', 'composer.phar', 'dev', 'sail', 'tests', 'public'].includes(name) ? 'text-green-600 dark:text-green-400' :'',
                    ['composer.json', 'composer.lock'].includes(name) ? 'text-blue-500 dark:text-blue-300' :'',
                    ['package.json', 'yarn.lock', 'package-lock.json',].includes(name) ? 'text-orange-600 dark:text-orange-400' :'',
                    !['artisan', 'composer', 'composer.phar', 'dev', 'sail', 'tests', 'public', 'composer.json', 'composer.lock', 'package.json', 'yarn.lock', 'package-lock.json',].includes(name) ? 'text-stone-700 dark:text-stone-100' :'',
                ]"
                @dblclick="$emit('openFile', file, name)"
            >
                <div class="w-4 h-4 flex-none"></div>

                <svg class="w-4 h-4 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="tracking-tight">{{ name }}</span>
            </button>
            <div v-if="!loading && (folder && open)">
                <FileOrFolder v-for="fileThing in files" @openFile="(f) => $emit('openFile', f, file)" :key="fileThing.absolute" :file="fileThing">
                    <ArrowPathIcon v-if="loading" slot="icon" class="w-4 h-4 animate-spin text-blue-500 dark:text-blue-300"></ArrowPathIcon>
                    <FolderIcon v-else-if="fileThing.is_directory" slot="icon" class="w-4 h-4 ml-4"></FolderIcon>
                    <DocumentIcon v-else slot="icon" class="w-4 h-4 ml-4"></DocumentIcon>
                </FileOrFolder>
            </div>
            <div v-if="loading" class="mx-8 animate-pulse">
                <div class="flex gap-1 items-center">
                    <MagnifyingGlassIcon class="w-4 h-4 text-blue-500 dark:text-blue-300"></MagnifyingGlassIcon>
                    <span class="text-stone-700 dark:text-stone-100">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ChevronRightIcon, ChevronDownIcon, DocumentIcon, FolderOpenIcon, ArrowPathIcon, MagnifyingGlassIcon } from "@heroicons/vue/24/solid";
import { FolderIcon } from "@heroicons/vue/24/outline";

export default {
  components: { ChevronRightIcon, ChevronDownIcon, DocumentIcon, FolderIcon, FolderOpenIcon, ArrowPathIcon, MagnifyingGlassIcon },
    props: ['file'],
    emits: ['openFile'],
    data: () => ({
      open: false,
      collapsed: true,
      files: [],
      loading: false,
    }),
    computed: {
        name() {
            return this.file.name;
        },
        folder() {
            return this.file.is_directory;
        },
    },
    methods: {
        onToggleFolderExpand() {
            if (this.open) {
                this.open = false;
                return;
            }
          this.loading = true;
            axios.get('/api/files/' + this.file.file_path)
                .then(({ data }) => {
                    this.files = data;
                    this.loading = false;
                    this.open = true;

                })
        },
    }
}
</script>

<style scoped>

</style>
