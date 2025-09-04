<<template>
  <div class="dashboard" :class="{ 'theme-dark': darkTheme }">
    <header class="dashboard__header">
      <h1 class="dashboard__title">Dashboard</h1>
      <div>
        <button 
          @click="$router.push('/')" 
          class="dashboard__btn dashboard__btn--secondary"
        >
          ← Back to Tickets
        </button>
      </div>
    </header>

    <div class="dashboard__content">
      <div class="dashboard__main-sections">
        <!-- Left Section: Ticket Analysis Chart -->
        <div class="dashboard__section dashboard__section--chart">
          <h2 class="dashboard__section-title">Ticket Analysis</h2>
          <div class="dashboard__chart-wrapper">
            <div v-if="fetchingStats" class="dashboard__loading">Loading chart...</div>
            <canvas v-else ref="categoryChart" class="dashboard__chart"></canvas>
          </div>
        </div>

        <!-- Right Section: Statistics -->
        <div class="dashboard__section dashboard__section--stats">
          <h2 class="dashboard__section-title">Statistics</h2>
          <div class="dashboard__stats-grid">
            <!-- Total Tickets -->
            <div class="dashboard__total-card">
              <span class="dashboard__total-label">Total Tickets</span>
              <span class="dashboard__total-number">{{ totalTickets || 0 }}</span>
            </div>

            <!-- Status Numbers -->
            <div class="dashboard__numbers-group">
              <div class="dashboard__number-item" v-for="(count, status) in statusCounts" :key="status">
                <span class="dashboard__number-label">
                  {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                </span>
                <span class="dashboard__number-value">{{ count || 0 }}</span>
              </div>
            </div>

            <!-- Category Numbers -->
            <div class="dashboard__numbers-group">
              <div 
                class="dashboard__number-item dashboard__number-item--category" 
                v-for="(count, category) in categoryCounts" 
                :key="category"
              >
                <span class="dashboard__number-label">
                  {{ category.charAt(0).toUpperCase() + category.slice(1) }}
                </span>
                <span class="dashboard__number-value">{{ count || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Chart from 'chart.js/auto'
import { themeStore } from '../stores/themeStore'

export default {
  name: 'Dashboard',
  data() {
    return {
      categoryCounts: {},
      statusCounts: {},
      totalTickets: 0,
      fetchingStats: false,
      chart: null
    }
  },
  computed: {
    darkTheme() {
      return themeStore.state.isDark
    }
  },
  created() {
    // Listen for theme changes from TicketList component
    window.addEventListener('themeChange', (event) => {
      this.darkTheme = event.detail.isDark;
      // Save theme preference to localStorage
      localStorage.setItem('theme', this.darkTheme ? 'dark' : 'light');
    });
  },
  mounted() {
    // Get theme from localStorage to persist across reloads
    const savedTheme = localStorage.getItem('theme') || 'light';
    this.darkTheme = savedTheme === 'dark';
    
    // Apply theme to document
    if (this.darkTheme) {
      document.documentElement.classList.add('theme-dark');
    } else {
      document.documentElement.classList.remove('theme-dark');
    }
    
    this.fetchStats();
  },
  beforeUnmount() {
    // Clean up event listener
    window.removeEventListener('themeChange');
  },
  beforeUnmount() {
    if (this.chart) {
      this.chart.destroy()
    }
  },
  watch: {
    darkTheme: {
      handler(newVal) {
        if (newVal) {
          document.documentElement.classList.add('theme-dark')
        } else {
          document.documentElement.classList.remove('theme-dark')
        }
        this.$nextTick(() => {
          if (this.chart) {
            this.chart.destroy()
          }
          this.renderChart()
        })
      },
      immediate: true
    }
  },
  methods: {
    async fetchStats() {
      this.fetchingStats = true
      try {
        const res = await fetch('/api/stats')
        const data = await res.json()
        this.categoryCounts = data.category || {}
        this.statusCounts = data.status || {}
        this.totalTickets = data.total || 0
        
        this.$nextTick(() => {
          this.renderChart()
        })
      } catch (error) {
        console.error('Error fetching stats:', error)
      } finally {
        this.fetchingStats = false
      }
    },
    renderChart() {
      if (!this.$refs.categoryChart) return
      
      if (this.chart) {
        this.chart.destroy()
      }
      
      const ctx = this.$refs.categoryChart.getContext('2d')
      const labels = Object.keys(this.categoryCounts).map(label => 
        label.charAt(0).toUpperCase() + label.slice(1)
      )
      const data = Object.values(this.categoryCounts)
      
      this.chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Number of Tickets',
            data,
            backgroundColor: ['#118ab2', '#06d6a0', '#ffd166', '#ef476f', '#073b4c'],
            borderWidth: 0,
            borderRadius: 4,
            maxBarThickness: 35
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: '#003049'
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: 1
              },
              grid: {
                display: false
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          }
        }
      })
    }
  }
}
</script>

<style scoped>
.dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
  background: #f9f9f9;
  min-height: 100vh;
}

.theme-dark .dashboard {
  background: #1a1d20;
  color: #e0e0e0;
}

.theme-dark .dashboard__section {
  background: #23272b;
  color: #e0e0e0;
  box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}

.dashboard__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5em;
  background: #003049;
  border-radius: 8px;
  padding: 1em 1.5em;
  box-shadow: 0 2px 8px rgba(244,162,97,0.12);
}

.theme-dark .dashboard__header {
  background: #003049;
  border: 1px solid #374151;
}

.dashboard__title {
  font-size: 2.8em;
  font-weight: 700;
  color: #fff;
  letter-spacing: 0.05em;
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.dark .dashboard__title {
  background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.dashboard__btn {
  padding: 0.6em 1.4em;
  font-weight: 600;
  font-size: 0.95em;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dashboard__btn--secondary {
  background: #83c5be;
  color: white;
}

.dashboard__back-btn:hover {
  opacity: 1;
}

.dashboard__main-sections {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.dashboard__section {
  background: white;
  border-radius: 8px;
  padding: 0.75rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.dark .dashboard__section {
  background: #1e293b;
}

.dashboard__section-title {
  font-size: 1rem;
  margin-bottom: 0.75rem;
  font-weight: 600;
  color: #118ab2;
}

.dark .dashboard__section-title {
  color: #83c5be;
}

.dashboard__chart-wrapper {
  height: 250px;
  position: relative;
}

.dashboard__loading {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: #64748b;
}

.dashboard__stats-grid {
  display: grid;
  gap: 0.75rem;
}

.dashboard__total-card {
  background: #118ab2;
  color: white;
  padding: 0.75rem;
  border-radius: 6px;
  text-align: center;
}

.dashboard__total-label {
  font-size: 0.75rem;
  opacity: 0.9;
}

.dashboard__total-number {
  display: block;
  font-size: 1.5rem;
  font-weight: 600;
  margin-top: 0.25rem;
}

.dashboard__numbers-group {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
  gap: 0.5rem;
}

.dashboard__number-item {
  background: #f1f5f9;
  padding: 0.5rem;
  border-radius: 6px;
  text-align: center;
}

.dark .dashboard__number-item {
  background: #334155;
}

.dashboard__number-label {
  font-size: 0.7rem;
  color: #64748b;
  display: block;
  margin-bottom: 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dark .dashboard__number-label {
  color: #94a3b8;
}

.dashboard__number-value {
  font-size: 1rem;
  font-weight: 600;
  color: #0f172a;
}

.dark .dashboard__number-value {
  color: #f1f5f9;
}

@media (max-width: 768px) {
  .dashboard__main-sections {
    grid-template-columns: 1fr;
  }
  
  .dashboard__chart-wrapper {
    height: 200px;
  }
  
  .dashboard__numbers-group {
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
  }
}
</style>
