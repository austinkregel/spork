<template>
  <div class="relative bg-white dark:bg-stone-950 overflow-hidden" :style="{ backgroundImage: 'url(' + background + ')' }">
    <div class="max-w-7xl mx-auto">
      <div class="relative z-10 pb-8 sm:pb-16 md:pb-20 w-full lg:pb-28 xl:pb-32">
        <div class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
          <slot></slot>
        </div>
      </div>
    </div>
  </div>
</template>
