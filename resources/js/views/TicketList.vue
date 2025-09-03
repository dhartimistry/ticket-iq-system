<template>
  <div class="ticket-list">
    <h1>Tickets</h1>
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
  </div>
</template>

<script>
export default {
  name: 'TicketList',
  data() {
    return {
      tickets: [],
    };
  },
  mounted() {
    fetch('/api/tickets')
      .then(res => res.json())
      .then(data => {
        this.tickets = data.data || [];
      });
  },
};
</script>

<style>
.ticket-list__table {
  width: 100%;
  border-collapse: collapse;
}
.ticket-list__table th, .ticket-list__table td {
  border: 1px solid #ccc;
  padding: 8px;
}
</style>
