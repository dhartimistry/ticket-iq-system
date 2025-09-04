<template>
  <div class="ticket-detail__modal">
    <div :class="modalContentClass">
      <h2 :class="titleClass">Ticket Detail</h2>
      <form @submit.prevent="saveTicket">
        <label :class="labelClass">Subject</label>
        <input v-model="form.subject" :class="inputClass" placeholder="Subject" />

        <label :class="labelClass">Body</label>
        <textarea v-model="form.body" :class="textareaClass" placeholder="Body"></textarea>

        <label :class="labelClass">Status</label>
        <select v-model="form.status" :class="selectClass">
          <option value="open">Open</option>
          <option value="pending">Pending</option>
          <option value="closed">Closed</option>
        </select>

        <label :class="labelClass">Category</label>
        <input v-model="form.category" :class="inputClass" placeholder="Category (leave empty for 'Uncategorized')" />

        <div class="ticket-detail__modal-content__category-actions">
          <button type="button" class="ticket-detail__modal-content__category-actions__button" @click="overrideCategory">Override AI Category</button>
          <button type="button" class="ticket-detail__modal-content__category-actions__button" @click="classifyTicket" :disabled="classifying">
            <span v-if="classifying">Classifying...</span>
            <span v-else>Classify</span>
          </button>
        </div>

        <label :class="labelClass">Internal Note</label>
        <textarea v-model="form.note" :class="textareaClass" placeholder="Internal Note"></textarea>

        <div class="ticket-detail__modal-content__actions">
          <button type="submit" class="ticket-detail__modal-content__actions__button">Save</button>
          <button type="button" class="ticket-detail__modal-content__actions__button ticket-detail__modal-content__actions__button--close" @click="$emit('close')">Close</button>
        </div>
      </form>

      <div v-if="error" :class="errorClass">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "TicketDetailModal",
  props: {
    id: { type: String, required: true },
    darkMode: { type: Boolean, default: false },
  },
  data() {
    return {
      ticket: null,
      form: {
        subject: "",
        body: "",
        status: "open",
        category: "",
        note: "",
      },
      error: "",
      classifying: false,
    };
  },
  computed: {
    modalContentClass() {
      return {
        'ticket-detail__modal-content': true,
        'ticket-detail__modal-content--dark-mode': this.darkMode,
      };
    },
    titleClass() {
      return {
        'ticket-detail__modal-content__title': true,
        'ticket-detail__modal-content__title--dark-mode': this.darkMode,
      };
    },
    labelClass() {
      return {
        'ticket-detail__modal-content__label': true,
        'ticket-detail__modal-content__label--dark-mode': this.darkMode,
      };
    },
    inputClass() {
      return {
        'ticket-detail__modal-content__input': true,
        'ticket-detail__modal-content__input--dark-mode': this.darkMode,
      };
    },
    textareaClass() {
      return {
        'ticket-detail__modal-content__textarea': true,
        'ticket-detail__modal-content__textarea--dark-mode': this.darkMode,
      };
    },
    selectClass() {
      return {
        'ticket-detail__modal-content__select': true,
        'ticket-detail__modal-content__select--dark-mode': this.darkMode,
      };
    },
    errorClass() {
      return {
        'ticket-detail__modal-content__error': true,
        'ticket-detail__modal-content__error--dark-mode': this.darkMode,
      };
    },
  },
  mounted() {
    this.fetchTicket();
    // Force dark mode for testing
    this.$emit('update:darkMode', true);
  },
  methods: {
    fetchTicket() {
      fetch(`/api/tickets/${this.id}`)
        .then((res) => {
          if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
          return res.json();
        })
        .then((data) => {
          this.ticket = data;
          this.form.subject = data.subject || "";
          this.form.body = data.body || "";
          this.form.status = data.status || "open";
          this.form.category = data.category || "";
          this.form.note = data.note || "";
        })
        .catch((error) => {
          console.error("Fetch error:", error);
          this.error = "Failed to load ticket. Please try again.";
        });
    },
    saveTicket() {
      if (!this.form.subject.trim()) {
        this.error = "Subject is required.";
        return;
      }
      if (!this.form.body.trim()) {
        this.error = "Body is required.";
        return;
      }
      fetch(`/api/tickets/${this.id}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(this.form),
      })
        .then((res) => {
          if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
          return res.json();
        })
        .then((data) => {
          this.ticket = data;
          this.error = "";
          // Close the modal after successful save
          this.$emit('close');
        })
        .catch((error) => {
          console.error("Save error:", error);
          this.error = "Failed to save ticket. Please try again.";
        });
    },
    overrideCategory() {
      const currentCategory = this.form.category || 'Uncategorized';
      const newCategory = prompt("Enter new category (leave empty for 'Uncategorized'):", currentCategory);
      if (newCategory !== null) {
        this.form.category = newCategory === 'Uncategorized' ? '' : newCategory;
      }
    },
    classifyTicket() {
      this.classifying = true;
      fetch(`/api/tickets/${this.id}/classify`, { method: "POST" })
        .then((res) => {
          if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
          return res.json();
        })
        .then((data) => {
          this.form.category = data.category;
          if ("confidence" in data) this.ticket.confidence = data.confidence;
          this.classifying = false;
          this.error = "";
        })
        .catch((error) => {
          console.error("Classify error:", error);
          this.error = "Failed to classify ticket. Please try again.";
          this.classifying = false;
        });
    },
  },
};
</script>

<style>
/* Block: ticket-detail__modal */
.ticket-detail__modal {
  position: fixed;
  inset: 0;
  background: rgba(15, 18, 26, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1200;
}

/* Block: ticket-detail__modal-content */
.ticket-detail__modal-content {
  background: #ffffff;
  color: #23272f;
  border-radius: 12px;
  padding: 1em 1.2em;
  max-width: 420px;
  width: 100%;
  box-shadow: 0 6px 28px rgba(0, 0, 0, 0.18);
  animation: 0.25s ease-out ticket-detail__modal-fade-in;
  font-family: "Inter", "Segoe UI", Arial, sans-serif;
  font-size: 0.92em;
}

.ticket-detail__modal-content--dark-mode {
  background: #353a40;
  color: #fff;
  border: none;
  box-shadow: none;
  transition: background 0.3s;
}

/* Element: ticket-detail__modal-content__title */
.ticket-detail__modal-content__title {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.8rem;
  text-align: center;
  border-bottom: 1px solid #b0b3b8;
  padding-bottom: 0.4rem;
}

.ticket-detail__modal-content__title--dark-mode {
  border-bottom: 0.5px solid #b0b3b8;
  color: #f5f7fa;
  text-shadow: none;
}

/* Element: ticket-detail__modal-content__input */
.ticket-detail__modal-content__input,
.ticket-detail__modal-content__textarea,
.ticket-detail__modal-content__select {
  width: 100%;
  margin-bottom: 0.75em;
  padding: 0.55em 0.8em;
  border-radius: 6px;
  border: 1px solid #cfd8dc;
  font-size: 0.9em;
  background: #f9fafb;
  color: #23272f;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.ticket-detail__modal-content__input:focus,
.ticket-detail__modal-content__textarea:focus,
.ticket-detail__modal-content__select:focus {
  border-color: #1976d2;
  box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.1);
  outline: none;
}

.ticket-detail__modal-content__textarea {
  resize: vertical;
  min-height: 60px;
}

.ticket-detail__modal-content__input--dark-mode,
.ticket-detail__modal-content__textarea--dark-mode,
.ticket-detail__modal-content__select--dark-mode {
  background: #3a4047;
  color: #e3f2fd;
  border: 0.5px solid #b0b3b8;
  box-shadow: none;
}

.ticket-detail__modal-content__input--dark-mode:focus,
.ticket-detail__modal-content__textarea--dark-mode:focus,
.ticket-detail__modal-content__select--dark-mode:focus {
  border-color: #1976d2;
  box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.18);
}

.ticket-detail__modal-content__input--dark-mode::placeholder,
.ticket-detail__modal-content__textarea--dark-mode::placeholder {
  color: #b0b3b8;
  opacity: 1;
}

/* Element: ticket-detail__modal-content__category-actions */
.ticket-detail__modal-content__category-actions {
  display: flex;
  gap: 0.6em;
  margin-bottom: 0.8em;
}

/* Element: ticket-detail__modal-content__actions */
.ticket-detail__modal-content__actions {
  margin-top: 1em;
  display: flex;
  gap: 1em;
  justify-content: flex-end;
}

/* Element: ticket-detail__modal-content__actions__button */
.ticket-detail__modal-content__actions__button,
.ticket-detail__modal-content__category-actions__button {
  background: #1a659e;
  color: #fff;
  border: none;
  border-radius: 7px;
  padding: 0.5em 1.2em;
  font-size: 1em;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, box-shadow 0.2s;
  font-family: inherit;
  box-shadow: 0 2px 8px rgba(33,150,243,0.18);
}

.ticket-detail__modal-content__actions__button--close {
  background: linear-gradient(90deg, #d32f2f 0%, #ff5252 100%);
}

.ticket-detail__modal-content__actions__button:hover,
.ticket-detail__modal-content__category-actions__button:hover {
  box-shadow: 0 4px 16px rgba(33,150,243,0.18);
  filter: brightness(1.08);
}

/* Element: ticket-detail__modal-content__label */
.ticket-detail__modal-content__label {
  font-size: 0.85em;
  font-weight: 700;
  margin-bottom: 0.25em;
  display: block;
  color: #37474f;
}

.ticket-detail__modal-content__label--dark-mode {
  color: #fff;
  font-weight: 700;
  letter-spacing: 0.5px;
}

/* Element: ticket-detail__modal-content__error */
.ticket-detail__modal-content__error {
  margin-top: 0.8em;
  font-size: 0.9em;
  font-weight: 500;
  color: #d32f2f;
  background: #fff0f0;
  padding: 0.5em;
  border-radius: 5px;
}

.ticket-detail__modal-content__error--dark-mode {
  color: #ff8a65;
  background: #23272f;
  border: 1.5px solid #d32f2f;
  box-shadow: 0 2px 8px rgba(211,47,47,0.18);
}

/* Animation */
@keyframes ticket-detail__modal-fade-in {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>