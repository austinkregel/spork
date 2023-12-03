<style scoped lang="css">
.prose {
    font-size: 0.875rem;
    line-height: 1.7142857;
}
@media (prefers-color-scheme: dark) {
    .dark\:prose-invert > blockquote {
        border-color: #fff;

    }
}
</style>

<template>
<div class="h-full" v-if="topic">
    <div class="flex w-full h-screen overflow-auto">
        <div class="w-1/2 flex flex-col items-center">
            <div class="w-full flex flex-wrap justify-between items-center font-medium text-gray-700 px-4 py-2 border-r border-gray-200 dark:border-gray-800">
                <div class="text-center">{{ topic.name }}</div>
                <button @click.prevent="stack = !stack" class="underline dark:text-gray-200">
                    {{ stack ? 'Full Preview': 'Edit'}}
                </button>
            </div>
            <div class="w-full relative flex flex-col" v-show="stack">
                <textarea
                    autofocus
                    @mouseenter="(e) => active = e.target"
                    @mouseleave="() => active = null"
                    @scroll="scrollSync"
                    @keydown.exact.tab="textareaTabMicro"
                    @keydown.ctrl="ctrlSSaving"
                    @paste.prevent="textareaPaste"
                    v-model="topic.settings.body"
                    class="w-full dark:border-gray-500 bg-white dark:bg-gray-600 sync-scrolling border-l-0 pb-5"
                    ref="textarea"
                    style="height: calc((100vh - 40px)/2);"
                >
                </textarea>

                <div class="bottom-0 w-32 text-center right-0 mr-2 -mt-6" :class="[showBold? 'font-bold': '']">
                    <div class="bg-gray-900 rounded opacity-75 -mt-px">
                        Resources [{{ topic?.settings?.links?.length }}]
                    </div>
                </div>
            </div>
            <div @mouseenter="(e) => active = e.target" @mouseleave="() => active = null" @scroll="scrollSync" class="flex-grow dark:border-gray-500 w-full border-r border-t border-gray-200 overflow-auto w-full flex mx-auto text-left sync-scrolling" ref="markdown">
                <Markdown :html="true" :source="markdownWithLinks" class="w-full py-8 mx-auto prose dark:prose-invert"  style="height: calc((100vh - 40px)/2); "/>
            </div>
        </div>
        <div class="w-1/2 flex flex-col overflow-y-scroll bg-gray-50 dark:bg-gray-700 gap-2 " ref="research">
            <input @keyup="searchDebounce(() => $store.dispatch('search', { search }), 250)" v-model="search" :placeholder="'Search: ' + topic.name" type="text" class="w-full px-4 py-2 bg-white dark:bg-gray-700 placeholder-gray-300 border-b border-t-0 border-l-0 border-r-0 border-gray-200 dark:border-gray-500">

            <div v-if="!$store.getters.researchLoading" v-for="(item, $i) in $store.getters.research.data" :key="'research'+$i" class="relative rounded-lg border border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-600 px-4 py-2 mx-2 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                <div class="flex-shrink-0">
                    <img class="h-10 w-10 rounded-full mt-2" :src="item.image" alt="">
                </div>
                <div class="flex-1 min-w-0 relative" v-if="item.image">
                    <button
                        @click.prevent="() => {
                            if (topic.settings.links.includes(item.link)) {
                                topic.settings.links = topic.settings.links.filter(link => item.link !== link)
                            } else {
                                topic.settings.links.push(item.link)
                            }
                            $store.dispatch('updateResearch', topic)
                        }"
                        class="w-8 h-8 absolute bottom-0 right-0 z-10 border rounded-full flex items-center justify-center bg-white dark:bg-gray-500 dark:border-gray-400 dark:text-gray-200">
                        <PlusIcon v-if="!topic.settings.links.includes(item.link)" class="w-3 h-3"></PlusIcon>
                        <CheckIcon v-else class="w-3 h-3"></CheckIcon>
                    </button>

                    <a :href="item.link" class="focus:outline-none" target="_blank">
                        <span class="absolute inset-0" aria-hidden="true"></span>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-50" v-html="item.title"></p>
                        <p class="text-sm text-gray-500 dark:text-gray-300" v-html="item.snippet"></p>
                        <a :href="item.link" class="text-sm text-green-500 dark:text-green-300" v-html="item.link"></a>
                    </a>
                </div>
                <pre v-else>{{ item }}</pre>
            </div>
            <div v-else class="px-2 py-4 italics">
                Loading...
            </div>

            <div v-if="!$store.getters.research.total && !$store.getters.researchLoading" class="px-4 italic text-gray-600 dark:text-gray-300">
                No search results
            </div>

            <div v-if="$store.getters.research?.data?.length > 0" class="flex w-full justify-between items-center mb-2 px-2">
                <button
                    :disabled="!$store.getters.research.prev_page_url"
                    :class="[' rounded p-2 border border-gray-200 dark:border-gray-500', !$store.getters.research.prev_page_url ? 'dark:bg-gray-500 dark:text-gray-100 bg-gray-100 text-gray-500 no-cursor opacity-75' : 'dark:bg-gray-600 dark:text-gray-50 bg-white text-gray-800 underline']"
                    @click="$store.dispatch('search', { search, url: $store.getters.research.prev_page_url })"
                >&laquo; Previous</button>
                <button
                    :disabled="!$store.getters.research.next_page_url"
                    :class="[' rounded p-2 border border-gray-200 dark:border-gray-500', !$store.getters.research.next_page_url ? 'dark:bg-gray-500 dark:text-gray-100 bg-gray-100 text-gray-500 no-cursor opacity-75' : 'dark:bg-gray-600 dark:text-gray-50 bg-white text-gray-800 underline']"
                    @click="$store.dispatch('search', { search, url: $store.getters.research.next_page_url })"
                >Next &raquo;</button>
            </div>
        </div>
    </div>
</div>
</template>

<script>
import {PlusIcon, HomeIcon, PencilIcon, ShoppingCartIcon, CheckIcon} from "@heroicons/vue/outline";
import 'highlight.js/styles/monokai.css';
import Markdown from 'vue3-markdown-it';
import {ref} from "vue";

export default {
    name: "Research",
    components: {
        Markdown,
        PlusIcon,
        CheckIcon
    },
    data() {
        return {
            topic: null,
            startOffset: 0,
            changes: [],
            showBold: false,
            stack: true,
        }
    },
    methods: {
        textareaPaste(e) {
            e.preventDefault();

            let clipboard = e.clipboardData.getData('text/plain') ?? '';

            if (clipboard.startsWith('https://') || clipboard.startsWith('http://')) {
                const url = new URL(clipboard);
                this.topic = {
                    ...this.topic,
                    settings: {
                        ...this.topic.settings,
                        links: [ ... new Set([...this.topic.settings.links, clipboard])]
                    }
                }

                clipboard = '[' + url.hostname+url.pathname + '](' + clipboard + ')';
            }

            const { start, end } = this.getCursorPos();
            this.textarea.value = this.textarea.value.substring(0, start) + clipboard + this.textarea.value.substring(end);

            // Minus 1 because the cursor is at the end of the inserted text
            this.textarea.selectionStart = this.textarea.selectionEnd = start + clipboard.length;
            this.topic.settings.body = this.textarea.value;
        },
        scrollSync() {
            [this.textarea, this.markdown].forEach(element => {
                if (!this.active) {
                    return;
                }
                if (element.isSameNode(this.active)) {
                    return;
                }
                element.scrollTop = this.active.scrollTop;
                element.scrollLeft = this.active.scrollLeft;
            });
        },
        ctrlSSaving(keyboardEvent) {
            if (keyboardEvent.key === 's') {
                keyboardEvent.preventDefault();
                console.log('saving', this.topic)
                this.$store.dispatch('updateFeature', this.topic)
            }
        },
        /** @var KeyboardEvent keyboardEvent */
        textareaTabMicro(keyboardEvent) {
            if (keyboardEvent.shiftKey || keyboardEvent.keyCode === 9) {
                console.log(keyboardEvent, Object.keys(keyboardEvent))
                return;
            }
            keyboardEvent.preventDefault();

            const { start, end } = this.getCursorPos();
            this.textarea.value = this.textarea.value.substring(0, start) + "    " + this.textarea.value.substring(end);
            this.textarea.selectionStart = this.textarea.selectionEnd = start + 4;
        },
        getCursorPos() {
            if ("selectionStart" in this.textarea && document.activeElement == this.textarea) {
                return {
                    start: this.textarea.selectionStart,
                    end: this.textarea.selectionEnd
                };
            }
            else if (this.textarea.createTextRange) {
                var sel = document.selection.createRange();
                if (sel.parentElement() === this.textarea) {
                    var rng = this.textarea.createTextRange();
                    rng.moveToBookmark(sel.getBookmark());
                    for (var len = 0;
                         rng.compareEndPoints("EndToStart", rng) > 0;
                         rng.moveEnd("character", -1)) {
                        len++;
                    }
                    rng.setEndPoint("StartToStart", this.textarea.createTextRange());
                    for (var pos = { start: 0, end: len };
                         rng.compareEndPoints("EndToStart", rng) > 0;
                         rng.moveEnd("character", -1)) {
                        pos.start++;
                        pos.end++;
                    }
                    return pos;
                }
            }
            return -1;
        },
        setTopic(newVal) {
            if (!newVal) {
                this.topic = null
                return null;
            }

            const topic = this.$store.getters.features?.research?.filter(topic => topic.id == newVal)[0];

            if (!topic.settings || !topic.settings.links) {
                topic.settings = {
                    body: '',
                    links: []
                }
            }

            this.topic = topic;
            this.$store.commit('setResearch', []);
        }
    },
    computed: {
        routes() {
            return [
                {
                    name: 'Home',
                    href: '/research',
                    icon: HomeIcon,
                    current: false,
                },
                {
                    name: 'Topics',
                    href: '#',
                    icon: PencilIcon,
                    current: false,
                    children: this.$store.getters.features?.research?.map(list => ({
                        name: list.name,
                        href: '/research/' + list.id,
                        current: false,
                    }))
                },
            ]
        },
        markdownWithLinks() {
            let markdown = this.topic.settings?.body ?? '';
            if (!markdown) {
                markdown = '';
            }

            if (this.topic.settings?.links.length === 0) {
                // If we don't have links, don't render the resources at the bottom.
                return markdown;
            }

            return markdown + "\n\n------------\n\n## Resources\n" + this.topic.settings?.links?.map((link, index) => ' ' + (index+1) +'. <a class="" href="'+ link +'" target="_blank">' + link + '</a>' + "\n")?.join('')
        }
    },
    watch: {
        '$route.params.id'(newVal, old) {
            this.setTopic(newVal);
            // this.$store
        },
        'topic.settings.links'(newVal, old) {
            this.showBold = true;
            setTimeout(() => {
                this.showBold = false;
            }, 200) 
        },
    },
    async mounted() {
        this.setTopic(this.$route.params.id)
    },
    setup() {
        function createDebounce() {
            let timeout = null;
            return function (fnc, delayMs) {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    fnc();
                }, delayMs || 100);
            };
        }

        const searchDebounce = createDebounce();

        return {
            active: ref(null),
            markdown: ref(null),
            textarea: ref(null),
            research: ref(null),
            search: ref(''),
            topic: ref(null),
            searchDebounce,
        }
    }
}
</script>
