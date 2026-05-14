<script setup>
import { ref } from 'vue';
import PollForm from './PollForm.vue';
import { usePollStore } from '@/stores/usePollStore';

const { polls, deletePoll, createPoll, editPoll } = usePollStore();

// false = on voit le tableau, true = on voit le formulaire
const showForm = ref(false);
const pollToEdit = ref(null);

function openCreate() {
  pollToEdit.value = null;  // pas de poll = mode création
  showForm.value = true;
}

function openEdit(poll) {
  pollToEdit.value = poll;  // poll fourni = mode édition
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  pollToEdit.value = null;
}

async function handleSubmit(infos) {
  if (pollToEdit.value) {
    await editPoll(pollToEdit.value.id, infos);
  } else {
    await createPoll(infos);
  }
  closeForm();
}

async function delPoll(id) {
  await deletePoll(id);
}
</script>

<template>
  <!-- FORMULAIRE : visible si showForm est true -->
  <div v-if="showForm">
    <PollForm
      :poll="pollToEdit"
      @submit="handleSubmit"
      @cancel="closeForm"
    />
  </div>

  <!-- TABLEAU : visible si showForm est false -->
  <div v-else>
    <button @click="openCreate" class="mb-4 bg-teal-600 dark:bg-purple-900 text-white px-3 py-1 rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 transition">
      + Créer un sondage
    </button>

    <p v-if="polls.length === 0">Aucun sondage.</p>
    <table v-else class="w-full border-collapse text-left">
      <thead>
        <tr>
          <th class="border px-3 py-2">Actions</th>
          <th class="border px-3 py-2">Titre</th>
          <th class="border px-3 py-2">Question</th>
          <th class="border px-3 py-2">Brouillon</th>
          <th class="border px-3 py-2">Début</th>
          <th class="border px-3 py-2">Fin</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="poll in polls" :key="poll.id">
          <td class="border px-3 py-2">
            <button @click="openEdit(poll)" class="bg-teal-600 dark:bg-purple-900 text-white px-3 py-1 rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 transition">
              Modifier
            </button>
            <button @click="delPoll(poll.id)" class="bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 transition">
              Supprimer
            </button>
          </td>
          <td class="border px-3 py-2">{{ poll.title || '-' }}</td>
          <td class="border px-3 py-2">{{ poll.question }}</td>
          <td class="border px-3 py-2">{{ poll.is_draft ? 'Oui' : 'Non' }}</td>
          <td class="border px-3 py-2">{{ poll.started_at || '-' }}</td>
          <td class="border px-3 py-2">{{ poll.ends_at || '-' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>