<script setup>

import CodeEditor from "simple-code-editor";
import DynamicIcon from "@/Components/DynamicIcon.vue";
import Button from "@/Components/Button.vue";
import hljs from "highlight.js";
import {parse} from "node-html-parser";
import {onMounted, ref} from "vue";
import Node from "node-html-parser/dist/nodes/node.js";
import hljsDefineVue from "highlightjs-vue";

const getLanguage = (lang) => {
  return hljs.getLanguage(lang) ?? 'vue';
}
const emits = defineEmits(['save', 'update:modelValue']);
const { modelValue, file } = defineProps({
  modelValue: {
    required: true
  },
  file: {
    type: Object,
    required: true
  }
});

onMounted(() => {
  hljsDefineVue(hljs);
  hljs.initHighlightingOnLoad();
})


const saveFile = (event, ...args) => {
  console.log('event', event, ...args)
  return;
  console.log('saving file', { ... openFile }, file.value)

  axios.put('/api/files/'+openFile.file_path, { content: file.value })
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
</script>

<template>
  <div class="w-full flex flex-col py-4 z-50 flex gap-4" >
    <div class="flex-grow  rounded-t-lg bg-gray-700" style="">
      <div class="group flex gap-2 items-center">
        <DynamicIcon icon-name="DocumentIcon" class="w-4 h-4" />
        <span>{{ file.name }}</span>
        <button @click="cancelFile">
          <DynamicIcon icon-name="XMarkIcon" class="opacity-50 group-hover:opacity-100 w-4 h-4" />
        </button>
      </div>
      <Button primary @click="saveFile" class="">Save</Button>
    </div>
  </div>
  <CodeEditor
      :model-value="modelValue"
      @update:model-value="(value) => emits('update:modelValue', value)"
      theme="github-dark"
      class="flex-grow relative z-10"
      font-size=".9rem"
      :display-language="true"
      @lang="getLanguage"
      height="calc(100vh - 140px)"
      :languages="[['php', 'PHP'], ['js', 'JavaScript'],['vue', 'Vue SFC'], ['html', 'HTML'], ['css', 'CSS'], ['json', 'JSON'], ['yaml', 'yml']]"
  />
</template>