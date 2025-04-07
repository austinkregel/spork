<template>
    <ServerInfrastucture title="SSH Console" :server="server">
      <div class="xl:pl-96">
        <div class="px-4 py-10 sm:px-6 lg:px-8 lg:py-6">
          <div>
            <div ref="xterm" class="xterm">
              <div></div>
            </div>
          </div>
        </div>
      </div>
    </ServerInfrastucture>
</template>

<script setup>
import 'xterm/css/xterm.css'
import { Terminal } from 'xterm'
import { FitAddon } from 'xterm-addon-fit'
import { WebLinksAddon } from 'xterm-addon-web-links'
import { Unicode11Addon } from 'xterm-addon-unicode11'
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import {onMounted, onRenderTracked, ref} from "vue";
import { AttachAddon } from '@xterm/addon-attach';

const { server } = defineProps({
    server: Object,
})
const xterm = ref(null);

onMounted(() => {

  const $term = new Terminal({
    allowProposedApi: true,
  })
  const $fitAddon = new FitAddon()
  $term.loadAddon($fitAddon)
  $term.loadAddon(new WebLinksAddon())
  $term.loadAddon(new Unicode11Addon())

  $term.open(xterm.value)
  $term.unicode.activeVersion = '11'
  $fitAddon.fit()
  $term.onTitleChange((title) => $emit('title-change', title))
  console.log('App.Models.Server.'+server.id, AdminChannel);


})
</script>
