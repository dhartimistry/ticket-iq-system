<template>
  <div class="ticket-list">
    <header class="ticket-list__header">
      <h1 class="ticket-list__title">Tickets</h1>
      <div>
        <button class="ticket-list__btn ticket-list__btn--secondary" @click="showModal = true">+ New Ticket</button>
        <button class="ticket-list__btn ticket-list__btn--success" @click="exportCSV">Export CSV</button>
        <button class="ticket-list__btn ticket-list__btn--primary ticket-list__theme-switch" @click="toggleTheme">
          <span class="ticket-list__theme-switch__label">Mode</span>
          <span class="ticket-list__theme-switch__slider" :class="{ 'ticket-list__theme-switch__slider--dark': darkTheme }"></span>
        </button>
      </div>
    </header>
    <div v-if="showModal" class="ticket-list__modal">
      <div class="ticket-list__modal-content">
        <h2 class="ticket-list__modal-title">Submit New Ticket</h2>
        <form @submit.prevent="submitTicket">
          <input v-model="newSubject" placeholder="Subject" required class="ticket-list__input" @input="validateSubject" />
          <div v-if="subjectError" class="ticket-list__validation">{{ subjectError }}</div>
          <textarea v-model="newBody" placeholder="Body" required class="ticket-list__textarea" @input="validateBody"></textarea>
          <div v-if="bodyError" class="ticket-list__validation">{{ bodyError }}</div>
          <div class="ticket-list__modal-actions">
            <button type="submit">Submit</button>
            <button type="button" @click="closeModal">Cancel</button>
          </div>
        </form>
        <div v-if="error" class="ticket-list__error">{{ error }}</div>
      </div>
    </div>
    <div class="ticket-list__controls">
      <div class="ticket-list__search-container">
        <svg class="ticket-list__search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
        <input v-model="search" placeholder="Search tickets..." class="ticket-list__search" />
        <button 
          v-if="search" 
          @click="clearSearch" 
          class="ticket-list__search-clear"
          type="button"
          title="Clear search"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <select v-model="status" class="ticket-list__filter">
        <option value="">All Status</option>
        <option value="open">Open</option>
        <option value="pending">Pending</option>
        <option value="closed">Closed</option>
      </select>
      <select v-model="category" class="ticket-list__filter">
        <option value="">All Categories</option>
        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
      </select>
    </div>
    
    <div class="ticket-list__content">
      <div class="ticket-list__table-container">
        <table class="ticket-list__table">
      <thead>
        <tr>
          <th>Subject</th>
          <th>Status</th>
          <th>Category</th>
          <th>Confidence</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="ticket in tickets" :key="ticket.id" @click="openDetail(ticket.id)" style="cursor:pointer;">
          <td>{{ ticket.subject }}</td>
          <td>{{ ticket.status }}</td>
          <td>
            <span :class="{ 'ticket-list__uncategorized': !ticket.category }">
              {{ ticket.category || 'Uncategorized' }}
            </span>
            <span v-if="ticket.note" class="ticket-list__badge" title="Internal note present">📝</span>
            <span v-if="ticket.explanation" class="ticket-list__icon" :title="ticket.explanation">❓</span>
          </td>
          <td>
            <span v-if="ticket.confidence !== null">{{ ticket.confidence }}</span>
            <span v-else>-</span>
          </td>
        </tr>
      </tbody>
    </table>
    <div class="ticket-list__pagination">
      <button @click="prevPage" :disabled="page === 1">Prev</button>
      <span>Page {{ page }}</span>
      <button @click="nextPage" :disabled="!hasMore">Next</button>
    </div>
      </div>
      
      <div class="ticket-list__chart-container">
        <div class="ticket-list__chart">
          <h3>Tickets Analytics</h3>
          <canvas id="categoryChart"></canvas>
        </div>
      </div>
    </div>
    <div v-if="showDetail" class="ticket-list__modal" :class="{ 'dark': darkTheme }">
      <div class="ticket-list__modal-content" :class="{ 'dark': darkTheme }">
        <TicketDetail :id="selectedId" @close="closeDetail" :darkMode="darkTheme" />
      </div>
    </div>
  </div>
</template>

<script>
import Chart from '../chart';
import TicketDetail from './TicketDetail.vue';
export default {
  name: 'TicketList',
  components: { TicketDetail },
  data() {
    return {
      tickets: [],
      search: '',
      status: '',
      category: '',
      categories: ['billing', 'technical', 'general', 'account', 'other', 'Uncategorized'],
      page: 1,
      hasMore: false,
      showModal: false,
      newSubject: '',
      newBody: '',
      error: '',
      showDetail: false,
      selectedId: null,
      subjectError: '',
      bodyError: '',
      darkTheme: false,
      categoryCounts: {},
      chart: null,
      fetchingStats: false,
      renderingChart: false,
    };
  },
  watch: {
    search: 'fetchTickets',
    status: 'fetchTickets',
    category: 'fetchTickets',
    page: 'fetchTickets',
    darkTheme() {
      // Re-render chart when theme changes to update text colors
      if (this.chart && Object.keys(this.categoryCounts).length > 0) {
        this.$nextTick(() => {
          this.renderChart();
        });
      }
    },
    categoryCounts: {
      handler(newVal, oldVal) {
        console.log('categoryCounts watcher triggered', { newVal, oldVal });
        if (Object.keys(newVal).length > 0 && JSON.stringify(newVal) !== JSON.stringify(oldVal)) {
          this.$nextTick(() => {
            setTimeout(() => {
              console.log('Watcher rendering chart');
              this.renderChart();
            }, 200);
          });
        }
      },
      deep: true
    }
  },
  mounted() {
    console.log('TicketList component mounted');
    this.fetchTickets();
    this.fetchStats();
  },
  methods: {
    async fetchTickets() {
      try {
        const params = new URLSearchParams({
          search: this.search,
          status: this.status,
          category: this.category,
          page: this.page,
        });
        const res = await fetch(`/api/tickets?${params}`);
        const data = await res.json();
        this.tickets = data.data || [];
        this.hasMore = data.next_page_url !== null;
      } catch (e) {
        this.tickets = [];
        this.hasMore = false;
      }
    },
    clearSearch() {
      this.search = '';
    },
    nextPage() {
      if (this.hasMore) this.page++;
    },
    prevPage() {
      if (this.page > 1) this.page--;
    },
    async submitTicket() {
      this.error = '';
      try {
        const res = await fetch('/api/tickets', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ subject: this.newSubject, body: this.newBody }),
        });
        const data = await res.json();
        if (data.id) {
          this.closeModal();
          this.newSubject = '';
          this.newBody = '';
          this.fetchTickets();
        } else {
          this.error = data.message || 'Error creating ticket.';
        }
      } catch {
        this.error = 'Error creating ticket.';
      }
    },
    closeModal() {
      this.showModal = false;
      this.error = '';
    },
    openDetail(id) {
      this.selectedId = id;
      this.showDetail = true;
    },
    closeDetail() {
      this.showDetail = false;
      this.selectedId = null;
    },
    validateSubject() {
      const subject = this.newSubject.trim();
      if (!subject) {
        this.subjectError = 'Subject is required.';
      } else if (subject.length < 5) {
        this.subjectError = 'Subject must be at least 5 characters.';
      } else {
        this.subjectError = '';
      }
    },
    validateBody() {
      const body = this.newBody.trim();
      if (!body) {
        this.bodyError = 'Body is required.';
      } else if (body.length < 10) {
        this.bodyError = 'Body must be at least 10 characters.';
      } else {
        this.bodyError = '';
      }
    },
    toggleTheme() {
      this.darkTheme = !this.darkTheme;
      if (this.darkTheme) {
        document.documentElement.classList.add('theme-dark');
      } else {
        document.documentElement.classList.remove('theme-dark');
      }
    },
    exportCSV() {
      const headers = ['Subject', 'Status', 'Category', 'Confidence', 'Note', 'Explanation'];
      const rows = this.tickets.map(t => [
        t.subject,
        t.status,
        t.category || 'Uncategorized',
        t.confidence ?? '-',
        t.note ?? '',
        t.explanation ?? ''
      ]);
      let csv = headers.join(',') + '\n';
      csv += rows.map(r => r.map(x => '"' + String(x).replace(/"/g, '""') + '"').join(',')).join('\n');
      const blob = new Blob([csv], { type: 'text/csv' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'tickets.csv';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },
    async fetchStats() {
      console.log('fetchStats called');
      if (this.fetchingStats) {
        console.log('Already fetching stats, skipping');
        return;
      }
      
      this.fetchingStats = true;
      try {
        const res = await fetch('/api/stats');
        const data = await res.json();
        console.log('Stats data received:', data);
        this.categoryCounts = data.category || {};
        console.log('Category counts set:', this.categoryCounts);
      } catch (error) {
        console.error('Error fetching stats:', error);
      } finally {
        this.fetchingStats = false;
      }
    },
    renderChart() {
      console.log('Rendering chart with categories:', this.categoryCounts);
      
      // Prevent rendering if already in progress
      if (this.renderingChart) {
        console.log('Chart render already in progress, skipping');
        return;
      }
      
      this.renderingChart = true;
      
      try {
        // Destroy existing chart
        if (this.chart) {
          console.log('Destroying existing chart');
          this.chart.destroy();
          this.chart = null;
        }
        
        // Get canvas element
        const ctx = document.getElementById('categoryChart');
        if (!ctx) {
          console.error('Canvas element not found');
          return;
        }
        
        // Ensure we have data
        const labels = Object.keys(this.categoryCounts);
        const data = Object.values(this.categoryCounts);
        
        if (labels.length === 0) {
          console.log('No data to render chart');
          return;
        }
        
        console.log('Chart data:', { labels, data });
        
        // Generate colors with specific color for Uncategorized
        const defaultColors = ['#42a5f5', '#66bb6a', '#ffa726', '#ab47bc', '#ec407a', '#26a69a', '#ff7043', '#8d6e63'];
        const backgroundColor = labels.map((label, index) => {
          return label === 'Uncategorized' ? '#ee9b00' : defaultColors[index % defaultColors.length];
        });
        
        this.chart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Tickets Analytics',
              data: data,
              backgroundColor: backgroundColor,
              borderRadius: 6,
              barPercentage: 0.6,
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: { enabled: true },
            },
            scales: {
              x: { 
                grid: { display: false },
                ticks: { 
                  color: this.darkTheme ? '#ffffff' : '#000000',
                  font: {
                    size: 12,
                    weight: '500'
                  }
                }
              },
              y: { 
                grid: { 
                  color: this.darkTheme ? 'rgba(224, 224, 224, 0.2)' : 'rgba(224, 224, 224, 0.3)',
                  lineWidth: 1
                }, 
                beginAtZero: true,
                ticks: { 
                  color: this.darkTheme ? '#ffffff' : '#000000',
                  stepSize: 1,
                  font: {
                    size: 11,
                    weight: '500'
                  }
                }
              },
            },
          },
        });
        console.log('Chart created successfully:', this.chart);
      } catch (error) {
        console.error('Error creating chart:', error);
      } finally {
        this.renderingChart = false;
      }
    },
    scrollToChart() {
      this.$refs.chartSection.scrollIntoView({ behavior: 'smooth' });
    },
    debugChart() {
      console.log('=== Chart Debug Info ===');
      console.log('Category counts:', this.categoryCounts);
      console.log('Canvas element:', document.getElementById('categoryChart'));
      console.log('Chart instance:', this.chart);
      console.log('Chart.js available:', typeof Chart);
      
      // Force re-fetch stats and render
      this.fetchStats();
    }
  }
};
</script>

<style>
.ticket-list {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1em;
  background: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.ticket-list__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5em;
  background: #003049;
  border-radius: 8px;
  padding: 1em 1.5em;
  box-shadow: 0 2px 8px rgba(244,162,97,0.12);
}
.ticket-list__title {
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
.ticket-list__btn {
  padding: 0.6em 1.4em;
  font-weight: 600;
  font-size: 0.95em;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-left: 1em;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.ticket-list__btn:first-child {
  margin-left: 0;
}
/* Variants */
.ticket-list__btn--primary {
  background: #0077b6;
  color: #fff;
}
.ticket-list__btn--primary:hover {
  background: #005f8c;
}
.ticket-list__btn--secondary {
  background: #f4a261;
  color: #fff;
}
.ticket-list__btn--secondary:hover {
  background: #e76f51;
}
.ticket-list__btn--success {
  background: #2e7d32;
  color: #fff;
}
.ticket-list__btn--success:hover {
  background: #1b5e20;
}
.ticket-list__btn--info {
  background: #42a5f5;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 0.5em 1.2em;
  margin-left: 0.5em;
  cursor: pointer;
  transition: background 0.2s;
  text-decoration: none;
  display: inline-block;
}
.ticket-list__btn--info:hover {
  background: #1976d2;
}
.ticket-list__modal {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.ticket-list__modal-content {
  background: #fff;
  padding: 2em;
  border-radius: 8px;
  min-width: 320px;
  max-width: 480px;
  width: 100%;
  box-shadow: 0 4px 16px rgba(0,0,0,0.10);
  border: 1px solid #eee;
}
.ticket-list__modal-title {
  font-size: 1.6em;
  font-weight: 900;
  color: #003049;
  margin-bottom: 1.2em;
  letter-spacing: 0.5px;
  text-align: center;
}
.ticket-list__input,
.ticket-list__textarea {
  width: 100%;
  margin-bottom: 1em;
  padding: 0.75em;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 1em;
  transition: border 0.3s;
  background: #fff;
}
.ticket-list__input:focus,
.ticket-list__textarea:focus {
  border-color: #007bff;
  outline: none;
}
.ticket-list__modal-actions {
  display: flex;
  gap: 1em;
  justify-content: flex-end;
}
.ticket-list__modal-actions button[type="submit"] {
  background-color: #1a659e;
  color: #fff;
  font-size: 1em;
  font-weight: 700;
  padding: 0.6em 1.5em;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  transition: background 0.3s;
}
.ticket-list__modal-actions button[type="submit"]:hover {
  background-color: #125a87;
}
.ticket-list__modal-actions button[type="button"] {
  background: #e0e0e0;
  color: #000;
  padding: 0.6em 1.5em;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  transition: background 0.2s;
}
.ticket-list__modal-actions button[type="button"]:hover {
  background: #bdbdbd;
}
.ticket-list__error {
  color: #c1121f;
  margin-top: 1em;
  text-align: center;
  font-weight: 600;
}
.ticket-list__controls {
  margin-bottom: 1.5em;
  display: flex;
  gap: 1em;
  flex-wrap: wrap;
  background: #fff;
  border-radius: 8px;
  padding: 1em 1.5em;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.ticket-list__content {
  display: flex;
  gap: 2em;
  align-items: flex-start;
}
.ticket-list__table-container {
  flex: 1;
  min-width: 0;
}
.ticket-list__chart-container {
  flex: 0 0 400px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(33,150,243,0.07);
  padding: 1.5em;
}
.ticket-list__chart h3 {
  margin: 0 0 1em 0;
  color: #263238;
  font-size: 1.2em;
  font-weight: 600;
}
.ticket-list__chart canvas {
  max-width: 100%;
  height: 300px !important;
}
.ticket-list__search-container {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.ticket-list__search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
  z-index: 1;
}

.ticket-list__search {
  width: 100%;
  padding: 12px 16px 12px 48px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  background: #ffffff;
  color: #1f2937;
  transition: all 0.2s ease;
}

.ticket-list__search:focus {
  outline: none;
  border-color: #3b82f6;
  background: #ffffff;
}

.ticket-list__search::placeholder {
  color: #9ca3af;
  font-weight: 400;
}

.ticket-list__search-clear {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.ticket-list__search-clear:hover {
  color: #6b7280;
  background: rgba(107, 114, 128, 0.1);
}
.ticket-list__filter {
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  background: #f9fafb;
  color: #1f2937;
  transition: border-color 0.2s ease;
}

.ticket-list__filter:focus {
  outline: none;
  border-color: #6b7280;
}
.ticket-list__pagination {
  margin-top: 1.5em;
  display: flex;
  gap: 1em;
  align-items: center;
  justify-content: center;
}
.ticket-list__pagination button {
  padding: 0.5em 1.5em;
  font-weight: bold;
  border-radius: 4px;
  border: none;
  background-color: #003049;
  color: #fff;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0,48,73,0.12);
  transition: background 0.3s;
}
.ticket-list__pagination button:disabled {
  background: #eee;
  color: #aaa;
  cursor: not-allowed;
}
.ticket-list__pagination button:hover:not(:disabled) {
  background-color: #0077b6;
}
.ticket-list__table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1.5em;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  overflow: hidden;
}
.ticket-list__table th, .ticket-list__table td {
  border-bottom: 1px solid #eee;
  padding: 12px;
  text-align: left;
  font-size: 1em;
}
.ticket-list__table th {
  background: #6b7280;
  color: #fff;
  font-weight: bold;
  border-bottom: 2px solid #eee;
}
.ticket-list__table tr:hover {
  background-color: #e0e0e0;
  transition: background 0.2s;
}
.ticket-list__badge {
  display: inline-block;
  margin-left: 0.3em;
  background: #ffe082;
  color: #333;
  border-radius: 8px;
  padding: 0.1em 0.5em;
  font-size: 0.9em;
}
.ticket-list__uncategorized {
  background: #ee9b00;
  color: #fff;
  padding: 0.2em 0.5em;
  border-radius: 4px;
  font-weight: 600;
}
.ticket-list__icon {
  margin-left: 0.3em;
  cursor: help;
  color: #1976d2;
  font-size: 1em;
}
.ticket-list__confidence {
  display: inline-flex;
  align-items: center;
}
.ticket-list__validation {
  color: #d32f2f;
  font-size: 0.98em;
  margin-bottom: 0.5em;
  font-weight: 600;
}
.ticket-list__dashboard {
  margin: 2em 0;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(33,150,243,0.07);
  padding: 2em;
  min-height: 400px;
}
/* 🌙 Dark mode – slick + modern */
.theme-dark {
  background: #181a1b !important;
  color: #e0e0e0 !important;
}
.theme-dark .ticket-list {
  background: #23272b;
  color: #e0e0e0;
}
.theme-dark .ticket-list__dashboard {
  background: #2c2f33;
  color: #e0e0e0;
  box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}
.theme-dark .ticket-list__header {
  background: #1f2937;
  border: 1px solid #374151;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}
.theme-dark .ticket-list__title {
  background: linear-gradient(135deg, #f8fafc 0%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.theme-dark .ticket-list__table th {
  background: #374151;
  color: #f9fafb;
}
.theme-dark .ticket-list__table td {
  background: #23272b;
  color: #e0e0e0;
}
.theme-dark .ticket-list__modal-content {
  background: #23272b;
  color: #e0e0e0;
  border-color: #444;
}
.theme-dark .ticket-list__modal-title {
  color: #fff;
  background: transparent;
  border-radius: 0;
  padding: 0;
  box-shadow: none;
}
.theme-dark .ticket-list__input,
.theme-dark .ticket-list__textarea,
.theme-dark .ticket-list__filter,
.theme-dark .ticket-list__search {
  background: #181a1b;
  color: #e0e0e0;
  border-color: #444;
}
.theme-dark .ticket-list__btn--primary {
  background: #4dabf7;
  color: #181a1b;
}
.theme-dark .ticket-list__btn--primary:hover {
  background: #1976d2;
  color: #fff;
}
.theme-dark .ticket-list__btn--secondary {
  background: #e0a86b;
  color: #181a1b;
}
.theme-dark .ticket-list__btn--secondary:hover {
  background: #e76f51;
  color: #fff;
}
.theme-dark .ticket-list__btn--success {
  background: #66bb6a;
  color: #181a1b;
}
.theme-dark .ticket-list__btn--success:hover {
  background: #388e3c;
  color: #fff;
}
.theme-dark .ticket-list__modal-actions button[type="submit"] {
  background-color: #1a659e;
  color: #fff;
}
.theme-dark .ticket-list__modal-actions button[type="submit"]:hover {
  background-color: #125a87;
}
.theme-dark .ticket-list__modal-actions button[type="button"] {
  background: #444;
  color: #ddd;
}
.theme-dark .ticket-list__modal-actions button[type="button"]:hover {
  background: #555;
}
.theme-dark .ticket-list__controls {
  background: #23272b;
  border-radius: 8px;
  box-shadow: 0 1px 8px rgba(0,0,0,0.12);
  padding: 1em 1.5em;
  border: 1px solid #444;
}
.theme-dark .ticket-list__chart-container {
  background: #2c2f33;
  color: #e0e0e0;
  box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}
.theme-dark .ticket-list__chart h3 {
  color: #f5f7fa;
}
.theme-dark .ticket-list__search-icon {
  color: #6b7280;
}

.theme-dark .ticket-list__search {
  background: #374151;
  color: #f9fafb;
  border-color: #4b5563;
}

.theme-dark .ticket-list__search:focus {
  border-color: #3b82f6;
  background: #1f2937;
}

.theme-dark .ticket-list__search::placeholder {
  color: #6b7280;
}

.theme-dark .ticket-list__search-clear {
  color: #6b7280;
}

.theme-dark .ticket-list__search-clear:hover {
  color: #9ca3af;
  background: rgba(156, 163, 175, 0.1);
}
.theme-dark .ticket-list__filter {
  background: #374151;
  color: #f9fafb;
  border: 1px solid #4b5563;
}

.theme-dark .ticket-list__filter:focus {
  border-color: #6b7280;
}

/* Badges & highlights */
.theme-dark .ticket-list__badge {
  background: #c1121f;
  color: #fff;
}
.theme-dark .ticket-list__uncategorized {
  background: #ee9b00;
  color: #fff;
}
.theme-dark .ticket-list__info {
  color: #4dabf7;
}
.theme-dark .ticket-list__pagination button {
  background: #374151;
  color: #f9fafb;
  border: 1px solid #4b5563;
}
.theme-dark .ticket-list__pagination button:hover:not(:disabled) {
  background: #4b5563;
  color: #ffffff;
}
.theme-dark .ticket-list__pagination button:disabled {
  background: #1f2937;
  color: #6b7280;
  border-color: #374151;
}

/* Switch styles */
.ticket-list__theme-switch {
  display: inline-flex;
  align-items: center;
  gap: 0.7em;
  position: relative;
  min-width: 90px;
  background: #fff;
  color: #23272b;
  border: 1px solid #e0e0e0;
}
.ticket-list__theme-switch__label {
  font-weight: 600;
  font-size: 0.98em;
}
.ticket-list__theme-switch__slider {
  width: 38px;
  height: 22px;
  background: #e0e0e0;
  border-radius: 12px;
  position: relative;
  transition: background 0.3s;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.ticket-list__theme-switch__slider::after {
  content: '';
  position: absolute;
  top: 3px;
  left: 3px;
  width: 16px;
  height: 16px;
  background: #23272b;
  border-radius: 50%;
  transition: left 0.3s, background 0.3s;
}
.ticket-list__theme-switch__slider--dark {
  background: #23272b;
}
.ticket-list__theme-switch__slider--dark::after {
  left: 19px !important;
  background: #fff;
}

/* Responsive design */
@media (max-width: 1024px) {
  .ticket-list__content {
    flex-direction: column;
  }
  .ticket-list__chart-container {
    flex: none;
    width: 100%;
  }
}
</style>
