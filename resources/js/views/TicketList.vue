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
      document.body.classList.toggle('theme-dark', this.darkTheme);
    },
    exportCSV() {
      const headers = ['Subject', 'Status', 'Category', 'Confidence'];
      const rows = this.tickets.map(t => [
        `"${t.subject || ''}"`,
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
  background: #003049;
  border-radius: 8px;
  padding: 1em 1.5em;
  box-shadow: 0 2px 8px rgba(244,162,97,0.12);
}
.ticket-list__title {
  font-size: 2.4em;
  font-weight: 900;
  color: #fff;
  text-shadow: 0 2px 8px rgba(0,0,0,0.12);
  letter-spacing: 1px;
  margin: 0;
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
  background-color: #0077b6;
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
  background-color: #003049;
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
.ticket-list__search {
  flex: 1;
  padding: 0.75em;
  border: 1px solid #0077b6;
  border-radius: 4px;
  font-size: 1em;
  background: #fff;
  color: #000;
}
.ticket-list__filter {
  padding: 0.75em;
  border: 1px solid #0077b6;
  border-radius: 4px;
  font-size: 1em;
  background: #fff;
  color: #000;
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
  background: #003049;
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
  background: #c1121f;
  color: #fff;
  font-size: 0.9em;
  border-radius: 4px;
  padding: 2px 6px;
  margin-left: 6px;
  box-shadow: 0 1px 4px rgba(193,18,31,0.08);
}
.ticket-list__info {
  margin-left: 6px;
  cursor: pointer;
  color: #0077b6;
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
/* 🌙 Dark mode – slick + modern */
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
.theme-dark .ticket-list__title {
  color: #e0e0e0;
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
  background: linear-gradient(135deg, #4dabf7, #1976d2);
  color: #fff;
}
.theme-dark .ticket-list__modal-actions button[type="submit"]:hover {
  background: linear-gradient(135deg, #1976d2, #0d47a1);
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
.theme-dark .ticket-list__search {
  background: #181a1b;
  color: #f4a261;
  border: 1px solid #f4a261;
  box-shadow: 0 1px 4px rgba(244,162,97,0.10);
}
.theme-dark .ticket-list__filter {
  background: #181a1b;
  color: #e0e0e0;
  border: 1px solid #0077b6;
  box-shadow: 0 1px 4px rgba(0,119,182,0.10);
}

/* Badges & highlights */
.theme-dark .ticket-list__badge {
  background: #c1121f;
  color: #fff;
}
.theme-dark .ticket-list__info {
  color: #4dabf7;
}
.theme-dark .ticket-list__pagination button {
  background: #2c2f33;
  color: #f4f4f4;
}
.theme-dark .ticket-list__pagination button:hover:not(:disabled) {
  background: #4dabf7;
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
</style>
