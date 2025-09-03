<template>
  <div class="ticket-list">
    <h1>Tickets</h1>
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
        <tr v-for="ticket in tickets" :key="ticket.id">
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
  </div>
</template>

<script>
export default {
  name: 'TicketList',
  data() {
    return {
      tickets: [],
      search: '',
      status: '',
      category: '',
      categories: ['billing', 'technical', 'general', 'account', 'other'],
      page: 1,
      hasMore: false,
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
  },
};
</script>

<style>
.ticket-list__controls {
  margin-bottom: 1em;
  display: flex;
  gap: 1em;
}
.ticket-list__search {
  flex: 1;
  padding: 0.5em;
}
.ticket-list__filter {
  padding: 0.5em;
}
.ticket-list__pagination {
  margin-top: 1em;
  display: flex;
  gap: 1em;
  align-items: center;
}
.ticket-list__table {
  width: 100%;
  border-collapse: collapse;
}
.ticket-list__table th, .ticket-list__table td {
  border: 1px solid #ccc;
  padding: 8px;
}
</style>
