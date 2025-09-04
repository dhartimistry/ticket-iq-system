<template>
  <div class="app" :class="{ 'app--dark': isDark }">
    <router-view />
  </div>
</template>

<script>
import { themeStore } from './stores/themeStore'

export default {
  name: 'App',
  computed: {
    isDark() {
      return themeStore.state.isDark
    }
  },
  created() {
    themeStore.init()
  }
};
</script>
