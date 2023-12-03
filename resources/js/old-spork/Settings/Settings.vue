<template>
    <div>
        <div class="divide-y divide-gray-200 pt-6">
            <div class="px-4 sm:px-6">
                <div>
                    <h2 class="text-lg font-medium leading-6 text-gray-900">Privacy</h2>
                    <p class="mt-1 text-sm text-gray-500">Ornare eu a volutpat eget vulputate. Fringilla commodo amet.</p>
                </div>
                <ul role="list" class="mt-2 divide-y divide-gray-200">
                    <SwitchGroup as="li" class="flex items-center justify-between py-4">
                        <div class="flex flex-col">
                            <SwitchLabel as="p" class="text-sm font-medium text-gray-900" passive>Available to hire</SwitchLabel>
                            <SwitchDescription class="text-sm text-gray-500">Nulla amet tempus sit accumsan. Aliquet turpis sed sit lacinia.</SwitchDescription>
                        </div>
                        <Switch v-model="availableToHire" :class="[availableToHire ? 'bg-teal-500' : 'bg-gray-200', 'relative ml-4 inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2']">
                            <span aria-hidden="true" :class="[availableToHire ? 'translate-x-5' : 'translate-x-0', 'inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                        </Switch>
                    </SwitchGroup>
                    <SwitchGroup as="li" class="flex items-center justify-between py-4">
                        <div class="flex flex-col">
                            <SwitchLabel as="p" class="text-sm font-medium text-gray-900" passive>Make account private</SwitchLabel>
                            <SwitchDescription class="text-sm text-gray-500">Pharetra morbi dui mi mattis tellus sollicitudin cursus pharetra.</SwitchDescription>
                        </div>
                        <Switch v-model="privateAccount" :class="[privateAccount ? 'bg-teal-500' : 'bg-gray-200', 'relative ml-4 inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2']">
                            <span aria-hidden="true" :class="[privateAccount ? 'translate-x-5' : 'translate-x-0', 'inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                        </Switch>
                    </SwitchGroup>
                    <SwitchGroup as="li" class="flex items-center justify-between py-4">
                        <div class="flex flex-col">
                            <SwitchLabel as="p" class="text-sm font-medium text-gray-900" passive>Allow commenting</SwitchLabel>
                            <SwitchDescription class="text-sm text-gray-500">Integer amet, nunc hendrerit adipiscing nam. Elementum ame</SwitchDescription>
                        </div>
                        <Switch v-model="allowCommenting" :class="[allowCommenting ? 'bg-teal-500' : 'bg-gray-200', 'relative ml-4 inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2']">
                            <span aria-hidden="true" :class="[allowCommenting ? 'translate-x-5' : 'translate-x-0', 'inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                        </Switch>
                    </SwitchGroup>
                    <SwitchGroup as="li" class="flex items-center justify-between py-4">
                        <div class="flex flex-col">
                            <SwitchLabel as="p" class="text-sm font-medium text-gray-900" passive>Allow mentions</SwitchLabel>
                            <SwitchDescription class="text-sm text-gray-500">Adipiscing est venenatis enim molestie commodo eu gravid</SwitchDescription>
                        </div>
                        <Switch v-model="allowMentions" :class="[allowMentions ? 'bg-teal-500' : 'bg-gray-200', 'relative ml-4 inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2']">
                            <span aria-hidden="true" :class="[allowMentions ? 'translate-x-5' : 'translate-x-0', 'inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                        </Switch>
                    </SwitchGroup>
                </ul>
            </div>
            <div class="mt-4 flex justify-end py-4 px-4 sm:px-6">
                <button type="button" class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Cancel</button>
                <button type="submit" class="ml-5 inline-flex justify-center rounded-md border border-transparent bg-sky-700 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Save</button>
            </div>
        </div>


    </div>
</template>

<script>
export default {
    name: "Dashboard",
    components: { },
    data() {
        return {
            allowMentions: true,
        }
    },
    mounted() {

    }
}
</script>
