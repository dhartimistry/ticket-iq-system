<template>
    <button 
        class="theme-toggle ticket-list__btn ticket-list__btn--secondary" 
        @click="toggleTheme"
        :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
    >
        <svg 
            class="theme-toggle__icon" 
            width="16" 
            height="16" 
            viewBox="0 0 24 24" 
            fill="none" 
            stroke="currentColor" 
            stroke-width="2"
            :style="{ transform: isDark ? 'rotate(180deg)' : 'rotate(0)' }"
        >
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9z" />
        </svg>
        <span class="theme-toggle__text">{{ isDark ? 'Light' : 'Dark' }}</span>
    </button>
</template>

<script>
import { themeStore } from '../stores/themeStore'

export default {
    name: 'ThemeToggle',
    computed: {
        isDark() {
            return themeStore.state.isDark
        }
    },
    methods: {
        toggleTheme() {
            themeStore.toggleTheme()
        }
    }
}
</script>

<style scoped>
.theme-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.6em 1.2em;
    transition: all 0.3s ease;
    background-color: #adb5bd !important;
    border-color: #adb5bd !important;
    color: #212529 !important;
}

.theme-toggle:hover {
    background-color: #ced4da !important;
    border-color: #ced4da !important;
}

.theme-toggle__icon {
    transition: transform 0.3s ease;
    color: #212529;
}

.theme-toggle__text {
    font-size: 0.9em;
    font-weight: 500;
}
</style>
