// Theme store using Vue's reactivity system
import { reactive } from 'vue'

const state = reactive({
    isDark: localStorage.getItem('theme') === 'dark'
})

export const themeStore = {
    state,
    toggleTheme() {
        state.isDark = !state.isDark
        localStorage.setItem('theme', state.isDark ? 'dark' : 'light')
        this.applyTheme()
    },
    applyTheme() {
        if (state.isDark) {
            document.documentElement.classList.add('theme-dark')
        } else {
            document.documentElement.classList.remove('theme-dark')
        }
    },
    init() {
        this.applyTheme()
    }
}
