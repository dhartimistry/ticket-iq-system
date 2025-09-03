<template>
  <div class="ticket-detail" v-if="ticket">
    <h2>Ticket Detail</h2>
    <form @submit.prevent="saveTicket">
      <label>Subject</label>
      <input v-model="form.subject" class="ticket-detail__input" />
      <label>Body</label>
      <textarea v-model="form.body" class="ticket-detail__textarea"></textarea>
      <label>Status</label>
      <select v-model="form.status" class="ticket-detail__select">
        <option value="open">Open</option>
        <option value="pending">Pending</option>
        <option value="closed">Closed</option>
      </select>
      <label>Category</label>
      <input v-model="form.category" class="ticket-detail__input" />
      <button type="button" @click="overrideCategory">Override AI Category</button>
      <button type="button" @click="classifyTicket" :disabled="classifying" style="margin-left:1em;">
        <span v-if="classifying">Classifying...</span>
        <span v-else>Classify</span>
      </button>
      <label>Internal Note</label>
      <textarea v-model="form.internal_note" class="ticket-detail__textarea"></textarea>
      <div class="ticket-detail__actions">
        <button type="submit">Save</button>
        <button type="button" @click="$emit('close')">Close</button>
      </div>
    </form>
    <div v-if="error" class="ticket-detail__error">{{ error }}</div>
  </div>
</template>

<script>
export default {
  name: 'TicketDetail',
  props: ['id'],
  data() {
    return {
      ticket: null,
      form: {
        subject: '',
        body: '',
        status: '',
        category: '',
        internal_note: '',
      },
      error: '',
      classifying: false,
    };
  },
  mounted() {
    this.fetchTicket();
  },
  methods: {
    fetchTicket() {
      fetch(`/api/tickets/${this.id}`)
        .then(res => res.json())
        .then(data => {
          this.ticket = data;
          this.form.subject = data.subject;
          this.form.body = data.body;
          this.form.status = data.status;
          this.form.category = data.category;
          this.form.internal_note = data.internal_note || '';
        })
        .catch(() => {
          this.error = 'Failed to load ticket.';
        });
    },
    saveTicket() {
      fetch(`/api/tickets/${this.id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(this.form),
      })
        .then(res => {
          if (!res.ok) throw new Error();
          return res.json();
        })
        .then(data => {
          this.ticket = data;
          this.error = '';
        })
        .catch(() => {
          this.error = 'Failed to save ticket.';
        });
    },
    overrideCategory() {
      // Logic to override AI category (could call /classify or allow manual edit)
      this.form.category = prompt('Enter new category:', this.form.category) || this.form.category;
    },
    classifyTicket() {
      this.classifying = true;
      fetch(`/api/tickets/${this.id}/classify`, { method: 'POST' })
        .then(res => res.json())
        .then(data => {
          this.form.category = data.category;
          if ('confidence' in data) this.ticket.confidence = data.confidence;
          this.classifying = false;
          this.error = '';
        })
        .catch(() => {
          this.error = 'Failed to classify ticket.';
          this.classifying = false;
        });
    },
  },
};
</script>

<style>
.ticket-detail {
  background: #fff;
  padding: 2em;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  max-width: 500px;
  margin: 2em auto;
}
.ticket-detail__input,
.ticket-detail__textarea,
.ticket-detail__select {
  width: 100%;
  margin-bottom: 1em;
  padding: 0.5em;
  border-radius: 4px;
  border: 1px solid #ccc;
}
.ticket-detail__actions {
  display: flex;
  gap: 1em;
}
.ticket-detail__error {
  color: #d32f2f;
  margin-top: 1em;
}
</style>
