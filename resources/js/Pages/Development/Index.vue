<script setup>
import ContextMenuItem from "@/Components/ContextMenus/ContextMenuButton.vue";
import hljs from 'highlight.js';
import AppLayout from "@/Layouts/AppLayout.vue";
import FileOrFolder from "@/Components/Spork/FileOrFolder.vue";
import ContextMenu from "@/Components/ContextMenus/ContextMenu.vue";
import {ref, watch} from "vue";
import CodeEditor from 'simple-code-editor';
import DynamicIcon from "@/Components/DynamicIcon.vue";
import { parse } from 'node-html-parser';
import Node from "node-html-parser/dist/nodes/node.js";
import hljsDefineVue from "highlightjs-vue";
import Button from "@/Components/Button.vue";
import SporkCodeEditor from "@/Components/Spork/SporkCodeEditor.vue";

hljsDefineVue(hljs);
hljs.initHighlightingOnLoad();

const { title, files } = defineProps({
    title: String,
    files: Object
});

const selectedContext = ref('');
const showContextModal = ref(false);

watch(selectedContext, (value) => {
    if (value) {
        showContextModal.value = true;
    }
});

const file = ref('');
const openFile = ref(false);

const openedFile = async (openedFile, component) => {

    console.log('opened file', {...openedFile}, component)
    const element = document.querySelectorAll('code.hljs')[0];
    if (element?.getAttribute('data-highlighted')) {
        element.removeAttribute('data-highlighted');
    }
    const { data } = await axios.get('/api/files/'+openedFile.file_path)

    if (typeof data === 'object') {
        file.value = JSON.stringify(data, null, 4);
    } else {
        file.value = data;
    }
    openFile.value = openedFile;
}
const getLanguage = (lang) => {
    return hljs.getLanguage(lang) ?? 'vue';
}
const saveFile = () => {
    console.log('saving file', { ... openFile.value }, file.value)

    axios.put('/api/files/'+openFile.value.file_path, { content: file.value })
        .then(({ data }) => {
            console.log('saved file', data)
        })
        .catch((e) => {
            console.error('error saving file', e)
        });
}
const cancelFile = () => {
    openFile.value = null;
}
const recursiveMap = (n) => {
    return n.children.map((node) => {
        return {
            name: node.name,
            children: node.children?.length > 0 ? recursiveMap(node) : 0,
        }
    });
}
</script>

<template>
    <AppLayout :title="title">
        <div class="w-full flex">
            <div class="w-1/5">
                <ContextMenu>
                    <template #default="{ open }">
                    <div class="py-8 px-4 overflow-y-scroll" style="max-height: calc(100vh - 70px);">
                        <div v-for="(item, i) in files" :key="item">
                            <FileOrFolder :file="item" @openFile="openedFile" />
                        </div>
                    </div>
                    </template>
                    <template #items="{ close }">
                        <div>
                            <ContextMenuItem @click="() => selectedContext = 'Use Trait'">Use Trait</ContextMenuItem>
                            <ContextMenuItem @click="() => selectedContext = 'Implement Interface/Trait'">Implement Interface/Trait</ContextMenuItem>
                            <ContextMenuItem @click="() => selectedContext = 'Add binding to container'">Add binding to container</ContextMenuItem>
                            <ContextMenuItem @click="() => selectedContext = 'Make unit test'">Make unit test</ContextMenuItem>
                        </div>
                    </template>
                </ContextMenu>
            </div>
            <div v-if="!openFile" class="w-1/2 mx-auto my-12">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 753 480.95111" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M372.67989,690.09163l-2-.03906a463.83342,463.83342,0,0,1,7.09961-66.28711c8.64844-46.88086,23.02929-77.66992,42.74316-91.51172l1.14844,1.63672C375.61934,566.22444,372.70333,688.85628,372.67989,690.09163Z" transform="translate(-223.5 -209.52444)" fill="#2f2e41"/><path d="M397.67989,689.61311l-2-.03906c.043-2.21484,1.293-54.41406,21.84277-68.8418l1.14844,1.63672C398.9504,636.21468,397.68965,689.08089,397.67989,689.61311Z" transform="translate(-223.5 -209.52444)" fill="#2f2e41"/><circle cx="209.54903" cy="314.54765" r="10.00001" fill="#d0084f"/><circle cx="204.59688" cy="400.54767" r="10" fill="#d0084f"/><path d="M393.01866,540.06667c1.87935,12.004-3.0189,22.7406-3.0189,22.7406s-7.9453-8.72583-9.82465-20.72986,3.01891-22.7406,3.01891-22.7406S391.1393,528.06264,393.01866,540.06667Z" transform="translate(-223.5 -209.52444)" fill="#2f2e41"/><path d="M425.70583,569.22047c-11.493,3.9422-22.91878.98963-22.91878.98963s7.20793-9.34412,18.70088-13.28632,22.9188-.98962,22.9188-.98962S437.19878,565.27828,425.70583,569.22047Z" transform="translate(-223.5 -209.52444)" fill="#2f2e41"/><path d="M426.07508,645.38161a31.13456,31.13456,0,0,1-16.06421.69366,28.37369,28.37369,0,0,1,29.172-10.00628A31.13431,31.13431,0,0,1,426.07508,645.38161Z" transform="translate(-223.5 -209.52444)" fill="#2f2e41"/><polygon points="606.671 467.453 593.531 467.453 587.28 416.768 606.674 416.769 606.671 467.453" fill="#9e616a"/><path d="M833.52257,689.71536l-42.3702-.00157v-.53592a16.49256,16.49256,0,0,1,16.49166-16.4914h.001l25.87827.001Z" transform="translate(-223.5 -209.52444)" fill="#2f2e41"/><polygon points="525.57 467.453 512.429 467.453 506.178 416.768 525.572 416.769 525.57 467.453" fill="#9e616a"/><path d="M752.421,689.71536l-42.3702-.00157v-.53592a16.49256,16.49256,0,0,1,16.49166-16.4914h.00105l25.87827.001Z" transform="translate(-223.5 -209.52444)" fill="#2f2e41"/><path d="M716.28867,393.14081l-18.19929-2.81212-5.87957,9.464-63.27234,16.12848.1713.87221a11.90415,11.90415,0,1,0,2.58765,12.30932L708.321,413.12185Z" transform="translate(-223.5 -209.52444)" fill="#9e616a"/><path d="M898.0541,381.87169a11.85506,11.85506,0,0,0-4.37548.841l.36312-.63329-80.44329-41.58032L802.631,358.229l83.63476,37.12523a11.89949,11.89949,0,1,0,11.78838-13.48252Z" transform="translate(-223.5 -209.52444)" fill="#9e616a"/><circle cx="736.07056" cy="267.73324" r="35.53801" transform="translate(130.38899 741.8887) rotate(-80.78252)" fill="#2f2e41"/><circle cx="512.26421" cy="70.76964" r="22.6708" fill="#a0616a"/><ellipse cx="512.57057" cy="48.4052" rx="24.50896" ry="14.70538" fill="#2f2e41"/><circle cx="515.02148" cy="22.67078" r="14.70537" fill="#2f2e41"/><path d="M718.91431,224.22982A14.70692,14.70692,0,0,1,732.08789,209.604a14.86918,14.86918,0,0,0-1.53183-.07951,14.70539,14.70539,0,0,0,0,29.41076,14.86917,14.86917,0,0,0,1.53183-.0795A14.70693,14.70693,0,0,1,718.91431,224.22982Z" transform="translate(-223.5 -209.52444)" fill="#2f2e41"/><path d="M723.97781,336.57587l1.82839-17.57652s24.80562-16.34735,33.23625-6.68558l50.38786,86.21281s31.323,11.13572,30.21575,53.658l-1.498,205.5398L802.631,661.61826,781.063,501.3681l-19.48674,166.026-41.35039-1.29476,3.72025-109.37556,19.71737-106.02732-.1889-35.18233-8.68389-14.19874s-15.90728-6.39038-16.35213-24.44983l-.34823-25.38571Z" transform="translate(-223.5 -209.52444)" fill="#2f2e41"/><path d="M749.98787,317.13922l.48927-8.23917s75.032,19.772,69.07954,33.90894-17.11318,18.60128-17.11318,18.60128l-43.155-17.11318Z" transform="translate(-223.5 -209.52444)" fill="#2f2e41"/><path d="M730.38083,337.64127l-5.64584-6.02061s-45.03187,63.189-31.41423,70.24916,25.0524,3.35351,25.0524,3.35351l22.22821-40.75684Z" transform="translate(-223.5 -209.52444)" fill="#2f2e41"/><path d="M640.24484,543.397,922.80569,486.993l-23.614-118.29636L616.63089,425.1006Z" transform="translate(-223.5 -209.52444)" fill="#fff"/><path d="M925.11811,488.5359,638.702,545.70941,614.31843,423.5577l286.41613-57.1735ZM641.78762,541.08463l278.70571-55.63437L897.64892,371.009,618.94321,426.64335Z" transform="translate(-223.5 -209.52444)" fill="#e4e4e4"/><rect x="649.55431" y="429.35966" width="233.18398" height="6.07982" transform="translate(-293.32159 -51.18186) rotate(-11.28883)" fill="#e4e4e4"/><rect x="654.1884" y="452.57456" width="233.18398" height="6.07982" transform="translate(-297.77636 -49.82557) rotate(-11.28883)" fill="#e4e4e4"/><rect x="658.84925" y="475.92356" width="233.18398" height="6.07982" transform="translate(-302.25687 -48.46145) rotate(-11.28883)" fill="#e4e4e4"/><path d="M770.62873,443.64449,762.631,445.241a2.24918,2.24918,0,0,1-2.643-1.76342L756.20675,424.535a2.24917,2.24917,0,0,1,1.76341-2.643l7.99772-1.59648a2.24918,2.24918,0,0,1,2.643,1.76342l3.78125,18.94256A2.24917,2.24917,0,0,1,770.62873,443.64449Z" transform="translate(-223.5 -209.52444)" fill="#d0084f"/><path d="M861.72707,449.59966l-7.99771,1.59648a2.24916,2.24916,0,0,1-2.643-1.76342l-3.78126-18.94255a2.24917,2.24917,0,0,1,1.76342-2.643l7.99772-1.59648a2.24917,2.24917,0,0,1,2.643,1.76342l3.78125,18.94255A2.24916,2.24916,0,0,1,861.72707,449.59966Z" transform="translate(-223.5 -209.52444)" fill="#d0084f"/><path d="M812.39337,483.72688l-7.99771,1.59648a2.24916,2.24916,0,0,1-2.643-1.76342l-3.78126-18.94255a2.24917,2.24917,0,0,1,1.76342-2.643l7.99772-1.59648a2.24917,2.24917,0,0,1,2.643,1.76342l3.78125,18.94256A2.24915,2.24915,0,0,1,812.39337,483.72688Z" transform="translate(-223.5 -209.52444)" fill="#d0084f"/><path d="M975.5,690.47556h-751a1,1,0,0,1,0-2h751a1,1,0,0,1,0,2Z" transform="translate(-223.5 -209.52444)" fill="#cacaca"/></svg>
            </div>
            <div v-else class="flex flex-wrap flex-grow pr-4">
              <SporkCodeEditor
                @save="saveFile"
                v-model="file"
                :file="openFile"
              />
            </div>
        </div>
    </AppLayout>
</template>
