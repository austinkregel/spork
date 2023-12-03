<template>
    <div>
        <slot></slot>
        <div class="flex flex-wrap">
            <div v-for="item in items" :class="width ? width : 'w-full md:w-1/2' ">
                <div class="flex flex-wrap items-center">
                    <img :src="item.imageLink" alt="" style="max-height: 80px;">
                    <div>
                        <div class="font-bold">
                            {{ item.name }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        props: ['items', 'width'],
        data() {
            return {}
        },
        mounted() {

        }
    }
</script>
