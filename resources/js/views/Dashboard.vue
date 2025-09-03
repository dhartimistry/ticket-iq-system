<template>
  <div class="dashboard">
    <h1>Dashboard</h1>
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
    <div class="dashboard__chart">
      <h2>Tickets by Category</h2>
      <!-- Chart.js chart will go here -->
      <canvas id="categoryChart"></canvas>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Dashboard',
  data() {
    return {
      statusCounts: {},
      categoryCounts: {},
      total: 0,
    };
  },
  mounted() {
    fetch('/api/stats')
      .then(res => res.json())
      .then(data => {
        this.statusCounts = data.status || {};
        this.categoryCounts = data.category || {};
        this.total = data.total || 0;
        // Chart.js integration can be added here
      });
  },
};
</script>

<style>
.dashboard__stats {
  display: flex;
  gap: 2em;
  margin-bottom: 2em;
  flex-wrap: wrap;
}
.dashboard__card {
  background: #f5f5f5;
  padding: 1em 2em;
  border-radius: 8px;
  min-width: 120px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.dashboard__card--total {
  background: #e0f7fa;
  font-weight: bold;
}
.dashboard__chart {
  margin-top: 2em;
}
</style>
