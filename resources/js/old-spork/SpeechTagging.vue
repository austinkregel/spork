<template>
    <div class="flex flex-col flex-1">
      <main class="flex-1 pb-8">
        <div class="mt-0">
            <button @click="() => exportToJsonFile()">Export To Json File</button>
            <div @mouseup="onClick">
                <div v-for="groups in groupedItems" :key="groups">
                    <div>
                        <group
                            :files="groups"
                            @rename="(fl) => renameFile(fl)"
                            @rename-all-files="(fl) => renameAllFile(fl)"
                            @remove-text-from-string="removeStringFromAllNames"
                        ></group>
                    </div>
                </div>
            </div>
            <div v-if="selection" class="border-t border-stone-500 pt-4">
                <p>Selected text: {{ selection }}</p>
                <p>Tag: <span v-for="word in selection.split(' ')">{{ word }}</span></p>
            </div>

        </div>
      </main>
    </div>
</template>
<script>
import parsedData from  '../../../storage/joke.json';
import { groupBy } from 'lodash';
import Group from './Group';
export default {
    components: { Group },
    data() {
        return {
            openGroups: [],
            selection: null,
            contextOpen: false,
            selectedPosition: {
                start: 0,
                end: 0,
            },
            tokens: [],
            selectedTokens: [],
            parsedData,
            menuPosition: {
                x: 0,
                y: 0
            },
            menuOptions: [
                "group",
                "name",
                "season",
                "episode",
                "names",
                "resolution",
                "start_year",
                "end_year",
                "year",
                "season",
                "episode",
                "file_type",
                "random",
            ].map((value) => ({
                name: value.replaceAll('_', ' '),
                value,
            })),
            selectedFile: null,
        }
    },
    methods: {
        exportToJsonFile() {
            let dataStr = JSON.stringify(this.parsedData);
            let dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);

            let exportFileDefaultName = 'data.json';

            let linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        },
        onClick(event) {
            let select = window.getSelection();
            if (select) {

                this.selectedPosition = {
                    start: select.getRangeAt(0).startOffset,
                    end: select.getRangeAt(0).endOffset,
                }
                this.selection = select.toString();
            } else {
                this.selection = null;
            }
        },
        renameFile(file) {
            const index = this.parsedData.indexOf(file);
            const newName = prompt("What's the new name of this file?", this.selection);

            this.parsedData = this.parsedData.map((parsedDatum) => {
                if (file.name !== parsedDatum.name) {
                    return parsedDatum
                }

                parsedDatum.name = newName
                return parsedDatum;
            });
        },
        renameAllFile(file) {
            const index = this.parsedData.indexOf(file);
            const newName = prompt("What's the new name of this file?", this.selection);

            this.parsedData = this.parsedData.map((parsedDatum) => {
                console.log('parts', file.name, '|', parsedDatum.name);
                if (file.name !== parsedDatum.name) {
                    return parsedDatum
                }

                parsedDatum.name = newName
                return parsedDatum;
            });
        },
        removeStringFromAllNames(file) {
            const newName = prompt("What text would you like to remove?", this.selection);

            this.parsedData = this.parsedData.map((parsedDatum) => {
                console.log(file.name, parsedDatum.name);
                if (parsedDatum?.name?.includes(newName) ?? false) {
                    console.log('parsed parttern', parsedDatum)
                    parsedDatum.name = parsedDatum.name.replace(newName, '');
                    return parsedDatum
                }

                return parsedDatum;
            });
        }
    },
    computed: {
        selectedPositions(oldValue, newValue) {
            if (this.selection) {
                this.selectedTokens = this.selection.split(' ').filter(f=>f);
            }
            // this.tokens = t
        },
        groupedItems() {
            return groupBy(this.parsedData.map(file => ({ ... file, name: file.name?.trim()})), 'name');
        }
    }
}

</script>
