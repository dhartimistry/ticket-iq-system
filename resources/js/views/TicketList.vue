<template>
  <div class="ticket-list">
    <header class="ticket-list__header">
      <h1 class="ticket-list__title">Tickets</h1>
      <button class="ticket-list__new-btn" @click="showModal = true">+ New Ticket</button>
    </header>
    <div v-if="showModal" class="ticket-list__modal">
      <div class="ticket-list__modal-content">
        <h2>Submit New Ticket</h2>
        <form @submit.prevent="submitTicket">
          <input v-model="newSubject" placeholder="Subject" required class="ticket-list__input" />
          <textarea v-model="newBody" placeholder="Body" required class="ticket-list__textarea"></textarea>
          <div class="ticket-list__modal-actions">
            <button type="submit">Submit</button>
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
          <td>{{ ticket.category || '-' }}</td>
          <td>{{ ticket.confidence !== null ? ticket.confidence : '-' }}</td>
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
</style>
