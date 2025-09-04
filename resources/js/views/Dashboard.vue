<template>
  <div :class="['dashboard', { 'dashboard--dark': isDarkMode }]">
    <header class="dashboard__header">
      <router-link to="/tickets" class="dashboard__btn dashboard__btn--back">← Back to Tickets</router-link>
      <h1>Helpdesk Dashboard</h1>
    </header>
    <div class="dashboard__stats">
      <div class="dashboard__card" v-for="(count, status) in statusCounts" :key="status">
        <strong>{{ status }}</strong>
        <div>{{ count }}</div>
      </div>
      <div class="dashboard__card" v-for="(count, category) in categoryCounts" :key="category">
        <strong>{{ category }}</strong>
        <div>{{ count }}</div>
      </div>
      <div class="dashboard__card dashboard__card--total">
        <strong>Total Tickets</strong>
        <div>{{ total }}</div>
      </div>
    </div>
    <div :class="['dashboard__chart', { 'dashboard__chart--dark': isDarkMode }]">
      <h2>Tickets by Category</h2>
      <canvas id="categoryChart"></canvas>
    </div>
  </div>
</template>

<script>
import Chart from '../chart';
export default {
  name: 'Dashboard',
  data() {
    return {
      statusCounts: {},
      categoryCounts: {},
      total: 0,
      chart: null,
    };
  },
  computed: {
    isDarkMode() {
      return document.documentElement.classList.contains('theme-dark');
    }
  },
  mounted() {
    fetch('/api/stats')
      .then(res => res.json())
      .then(data => {
        this.statusCounts = data.status || {};
        this.categoryCounts = data.category || {};
        this.total = data.total || 0;
        this.renderChart();
      });
  },
  methods: {
    renderChart() {
      if (this.chart) this.chart.destroy();
      const ctx = document.getElementById('categoryChart');
      
      // Ensure we have proper labels for all categories, including uncategorized
      const labels = Object.keys(this.categoryCounts);
      const data = Object.values(this.categoryCounts);
      
      // Generate colors with specific color for Uncategorized
      const defaultColors = ['#42a5f5', '#66bb6a', '#ffa726', '#ab47bc', '#ec407a', '#26a69a', '#ff7043', '#8d6e63'];
      const backgroundColor = labels.map((label, index) => {
        return label === 'Uncategorized' ? '#ee9b00' : defaultColors[index % defaultColors.length];
      });
      
      this.chart = new Chart(ctx, {
        type: 'pie',
        data: {
          labels: labels,
          datasets: [{
            label: 'Tickets by Category',
            data: data,
            backgroundColor: backgroundColor,
            borderWidth: 2,
            borderColor: this.isDarkMode ? '#2c2f33' : '#fff',
          }],
        },
        options: {
          responsive: true,
          plugins: {
            legend: { 
              display: true,
              position: 'bottom',
              labels: {
                color: this.isDarkMode ? '#e0e0e0' : '#333',
                padding: 20,
                usePointStyle: true,
                font: {
                  size: 12
                }
              }
            },
            tooltip: { 
              enabled: true,
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.parsed;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = ((value / total) * 100).toFixed(1);
                  return `${label}: ${value} (${percentage}%)`;
                }
              }
            },
          },
          maintainAspectRatio: false,
        },
      });
    },
  },
};
</script>

<style>
.dashboard {
  max-width: 900px;
  margin: 0 auto;
  padding: 2em 1em;
  background: #f9fbfd;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(60,80,120,0.08);
}
.dashboard--dark {
  background: #23272b;
  color: #e0e0e0;
  box-shadow: 0 4px 24px rgba(0,0,0,0.3);
}
.dashboard__header {
  text-align: center;
  margin-bottom: 2em;
}
.dashboard__header h1 {
  font-size: 2.2em;
  font-weight: 700;
  color: #263238;
  letter-spacing: 1px;
}
.dashboard--dark .dashboard__header h1 {
  color: #f5f7fa;
  text-shadow: 0 1px 3px rgba(0,0,0,0.3);
}
.dashboard__stats {
  display: flex;
  gap: 2em;
  margin-bottom: 2em;
  flex-wrap: wrap;
  justify-content: center;
}
.dashboard__card {
  background: linear-gradient(135deg, #e3f2fd 0%, #fff 100%);
  padding: 1.2em 2em;
  border-radius: 12px;
  min-width: 140px;
  text-align: center;
  box-shadow: 0 2px 12px rgba(33,150,243,0.07);
  transition: box-shadow 0.2s;
  font-size: 1.1em;
}
.dashboard__card:hover {
  box-shadow: 0 4px 24px rgba(33,150,243,0.15);
}
.dashboard__card--total {
  background: linear-gradient(135deg, #e0f7fa 0%, #fff 100%);
  font-weight: bold;
  border: 2px solid #26a69a;
}
.dashboard--dark .dashboard__card {
  background: #2c2f33;
  color: #e0e0e0;
  box-shadow: 0 2px 12px rgba(0,0,0,0.2);
}
.dashboard--dark .dashboard__card--total {
  background: #2c2f33;
  border: 2px solid #26a69a;
}
.dashboard__chart {
  margin-top: 2em;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(33,150,243,0.07);
  padding: 2em;
  min-height: 400px;
}
.dashboard__chart--dark {
  background: #2c2f33;
  color: #fff;
  box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}
.dashboard__chart h2 {
  color: #263238;
  font-weight: 600;
  margin-bottom: 1em;
  font-size: 1.5em;
}
.dashboard__chart--dark h2 {
  color: #f5f7fa;
}
.dashboard__btn--back {
  background: #26a69a;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 0.5em 1.2em;
  margin-bottom: 1em;
  cursor: pointer;
  transition: background 0.2s;
  text-decoration: none;
  display: inline-block;
  font-weight: 500;
}
.dashboard__btn--back:hover {
  background: #00897b;
}
.dashboard--dark .dashboard__btn--back {
  background: #26a69a;
  color: #fff;
  box-shadow: 0 2px 8px rgba(38,166,154,0.3);
}
.dashboard--dark .dashboard__btn--back:hover {
  background: #00897b;
  box-shadow: 0 4px 16px rgba(38,166,154,0.4);
}
@media (max-width: 700px) {
  .dashboard {
    padding: 1em 0.5em;
  }
  .dashboard__stats {
    gap: 1em;
  }
  .dashboard__chart {
    padding: 1em;
  }
}
</style>
