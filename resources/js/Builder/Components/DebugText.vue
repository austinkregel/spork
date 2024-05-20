<!-- This example requires Tailwind CSS v2.0+ -->
<template>
  <div class="">
      <div>hello</div>
      <div>hello</div>
      <div>hello</div>
      <div>hello</div>
      <div>hello</div>
      <div>hello</div>

  </div>
</template>

<script>
export default {
    props: {
        name: {
            type: String,
            required: true,
            default: () => 'My Debug Component'
        },
        editor: {
            type: Object,
            required: false,
            default: () => {}
        }
    },
    mounted() {
    }
}
</script>
