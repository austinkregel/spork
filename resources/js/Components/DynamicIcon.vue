<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import * as OutlinedIcons from '@heroicons/vue/24/outline'
import * as SolidIcons from '@heroicons/vue/24/solid'
import { WalletIcon } from '@heroicons/vue/24/outline';
const props = defineProps({
    iconName: String,
    class: String,
    active: Boolean
});

const icons = computed(() => {
const activeIconSet = props.active ? SolidIcons : OutlinedIcons;
return {
  ...activeIconSet,
  ArticleIcon: activeIconSet.NewspaperIcon,
  ConditionIcon: activeIconSet.CodeBracketIcon,
  CredentialIcon: activeIconSet.LockClosedIcon,
  DomainIcon: activeIconSet.LinkIcon,
  ExternalRssFeedIcon: activeIconSet.RssIcon,
  AccountIcon: activeIconSet.BuildingOfficeIcon,
  BudgetIcon: activeIconSet.WalletIcon,
  NavigationIcon: activeIconSet.ArrowTopRightOnSquareIcon,
  PageIcon: activeIconSet.DocumentIcon,
  PersonIcon: activeIconSet.UsersIcon,
  ProjectIcon: activeIconSet.BuildingStorefrontIcon,
  ResearchIcon: activeIconSet.BeakerIcon,
  ScriptIcon: activeIconSet.CodeBracketSquareIcon,
  ThreadIcon: activeIconSet.ChatBubbleLeftRightIcon,
}
})
</script>

<template>
    <Component
        :is="icons[props.iconName]"
        :class="class"
    />
</template>
