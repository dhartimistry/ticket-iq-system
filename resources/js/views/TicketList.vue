<template>
  <div class="ticket-list">
    <header class="ticket-list__header">
      <h1 class="ticket-list__title">Tickets</h1>
      <div>
        <button class="ticket-list__new-btn" @click="showModal = true">+ New Ticket</button>
        <button class="ticket-list__theme-btn" @click="toggleTheme">
          {{ darkTheme ? 'Light Mode' : 'Dark Mode' }}
        </button>
        <button class="ticket-list__csv-btn" @click="exportCSV">Export CSV</button>
      </div>
    </header>
    <div v-if="showModal" class="ticket-list__modal">
      <div class="ticket-list__modal-content">
        <h2>Submit New Ticket</h2>
        <form @submit.prevent="submitTicket">
          <input v-model="newSubject" placeholder="Subject" required class="ticket-list__input" @input="validateSubject" />
          <div v-if="subjectError" class="ticket-list__validation">{{ subjectError }}</div>
          <textarea v-model="newBody" placeholder="Body" required class="ticket-list__textarea" @input="validateBody"></textarea>
          <div v-if="bodyError" class="ticket-list__validation">{{ bodyError }}</div>
          <div class="ticket-list__modal-actions">
            <button type="submit" :disabled="subjectError || bodyError">Submit</button>
            <button type="button" @click="closeModal">Cancel</button>
          </div>
        </form>
        <div v-if="error" class="ticket-list__error">{{ error }}</div>
      </div>
    </div>
    <div class="ticket-list__controls">
      <input v-model="search" placeholder="Search..." class="ticket-list__search" />
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
            {{ ticket.category || '-' }}
            <span v-if="ticket.note" class="ticket-list__badge" title="Internal note present">📝</span>
          </td>
          <td>
            <span v-if="ticket.confidence !== null" class="ticket-list__confidence">
              {{ ticket.confidence }}
              <span v-if="ticket.explanation" class="ticket-list__info" :title="ticket.explanation">ℹ️</span>
            </span>
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
    <div v-if="showDetail" class="ticket-list__modal">
      <div class="ticket-list__modal-content">
        <TicketDetail :id="selectedId" @close="closeDetail" />
      </div>
    </div>
  </div>
</template>

<script>
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
      categories: ['billing', 'technical', 'general', 'account', 'other'],
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
    };
  },
  watch: {
    search: 'fetchTickets',
    status: 'fetchTickets',
    category: 'fetchTickets',
    page: 'fetchTickets',
  },
  mounted() {
    this.fetchTickets();
  },
  methods: {
    fetchTickets() {
      const params = new URLSearchParams({
        search: this.search,
        status: this.status,
        category: this.category,
        page: this.page,
      });
      fetch(`/api/tickets?${params}`)
        .then(res => res.json())
        .then(data => {
          this.tickets = data.data || [];
          this.hasMore = data.next_page_url !== null;
        });
    },
    nextPage() {
      if (this.hasMore) this.page++;
    },
    prevPage() {
      if (this.page > 1) this.page--;
    },
    submitTicket() {
      this.error = '';
      fetch('/api/tickets', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ subject: this.newSubject, body: this.newBody }),
      })
        .then(res => res.json())
        .then(data => {
          if (data.id) {
            this.closeModal();
            this.newSubject = '';
            this.newBody = '';
            this.fetchTickets();
          } else {
            this.error = data.message || 'Error creating ticket.';
          }
        })
        .catch(() => {
          this.error = 'Error creating ticket.';
        });
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
      if (!this.newSubject.trim()) {
        this.subjectError = 'Subject is required.';
      } else if (this.newSubject.length < 5) {
        this.subjectError = 'Subject must be at least 5 characters.';
      } else {
        this.subjectError = '';
      }
    },
    validateBody() {
      if (!this.newBody.trim()) {
        this.bodyError = 'Body is required.';
      } else if (this.newBody.length < 10) {
        this.bodyError = 'Body must be at least 10 characters.';
      } else {
        this.bodyError = '';
      }
    },
    toggleTheme() {
      this.darkTheme = !this.darkTheme;
      document.body.classList.toggle('theme-dark', this.darkTheme);
    },
    exportCSV() {
      const headers = ['Subject', 'Status', 'Category', 'Confidence'];
      const rows = this.tickets.map(t => [
        '"' + (t.subject || '') + '"',
        t.status || '',
        t.category || '',
        t.confidence !== null ? t.confidence : ''
      ]);
      let csv = headers.join(',') + '\n';
      csv += rows.map(r => r.join(',')).join('\n');
      const blob = new Blob([csv], { type: 'text/csv' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'tickets.csv';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },
  },
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
}
.ticket-list__title {
  font-size: 2.4em;
  font-weight: 900;
  color: #007bff;
  text-shadow: 0 2px 8px rgba(0,123,255,0.12);
  letter-spacing: 1px;
  margin: 0;
}
.ticket-list__new-btn {
  padding: 0.5em 1.5em;
  font-weight: bold;
  color: #fff;
  background-color: #007bff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
.ticket-list__new-btn:hover {
  background-color: #0056b3;
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
  max-width: 600px;
  width: 100%;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}
.ticket-list__input, .ticket-list__textarea {
  width: 100%;
  margin-bottom: 1em;
  padding: 0.75em;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.ticket-list__input:focus, .ticket-list__textarea:focus {
  border-color: #007bff;
  outline: none;
}
.ticket-list__modal-actions {
  display: flex;
  gap: 1em;
}
.ticket-list__error {
  color: red;
  margin-top: 1em;
}
.ticket-list__controls {
  margin-bottom: 1.5em;
  display: flex;
  gap: 1em;
  flex-wrap: wrap;
}
.ticket-list__search {
  flex: 1;
  padding: 0.75em;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.ticket-list__filter {
  padding: 0.75em;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.ticket-list__pagination {
  margin-top: 1.5em;
  display: flex;
  gap: 1em;
  align-items: center;
}
.ticket-list__table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1.5em;
}
.ticket-list__table th, .ticket-list__table td {
  border: 1px solid #ddd;
  padding: 12px;
  text-align: left;
}
.ticket-list__table th {
  background-color: #f2f2f2;
  font-weight: bold;
}
.ticket-list__table tr:hover {
  background-color: #f1f1f1;
}
.ticket-list__badge {
  display: inline-block;
  background: #ffe066;
  color: #333;
  font-size: 0.9em;
  border-radius: 4px;
  padding: 2px 6px;
  margin-left: 6px;
}
.ticket-list__info {
  margin-left: 6px;
  cursor: pointer;
  color: #007bff;
}
.ticket-list__confidence {
  display: inline-flex;
  align-items: center;
}
.ticket-list__validation {
  color: #d32f2f;
  font-size: 0.98em;
  margin-bottom: 0.5em;
}
.theme-dark {
  background: #181a1b !important;
  color: #e0e0e0 !important;
}
.theme-dark .ticket-list {
  background: #23272b;
  color: #e0e0e0;
}
.theme-dark .ticket-list__header {
  background: #23272b;
}
.theme-dark .ticket-list__table th {
  background: #23272b;
  color: #e0e0e0;
}
.theme-dark .ticket-list__table td {
  background: #23272b;
  color: #e0e0e0;
}
.theme-dark .ticket-list__modal-content {
  background: #23272b;
  color: #e0e0e0;
}
.theme-dark .ticket-list__input,
.theme-dark .ticket-list__textarea,
.theme-dark .ticket-list__filter,
.theme-dark .ticket-list__search {
  background: #181a1b;
  color: #e0e0e0;
  border-color: #444;
}
.theme-dark .ticket-list__new-btn,
.theme-dark .ticket-list__theme-btn {
  background: #444;
  color: #e0e0e0;
}
.ticket-list__theme-btn {
  margin-left: 1em;
  padding: 0.5em 1.2em;
  font-weight: bold;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  background: #e0e0e0;
  color: #23272b;
}
.ticket-list__theme-btn:hover {
  background: #bdbdbd;
}
.ticket-list__csv-btn {
  margin-left: 1em;
  padding: 0.5em 1.2em;
  font-weight: bold;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  background: #4caf50;
  color: #fff;
}
.ticket-list__csv-btn:hover {
  background: #388e3c;
}
.theme-dark .ticket-list__csv-btn {
  background: #388e3c;
  color: #e0e0e0;
}
</style>
