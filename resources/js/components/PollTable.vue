<script setup>
import { ref } from 'vue';
import PollForm from './PollForm.vue';
import { usePollStore } from '@/stores/usePollStore';
import { useFormatDate } from '@/composables/useFormatDate';

const { formatDate, formatDateShort } = useFormatDate();
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

const copied = ref(null); // stocke l'id du poll dont le lien vient d'être copié

async function copyLink(poll) {
  const url = `${window.location.origin}/polls/${poll.secret_token}`;
  await navigator.clipboard.writeText(url);
  copied.value = poll.id;
  setTimeout(() => copied.value = null, 2000); // remet à null après 2 secondes
}

</script>

<template>
  <!-- FORMULAIRE : visible si showForm est true -->
  <div v-if="showForm">
    <PollForm :poll="pollToEdit" @submit="handleSubmit" @cancel="closeForm" />
  </div>

  <!-- TABLEAU : visible si showForm est false -->
  <div v-else>
    <button @click="openCreate"
      class="mb-4 bg-teal-600 dark:bg-purple-900 text-white px-3 py-1 rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 transition">
      + Créer un sondage
    </button>

    <p v-if="polls.length === 0">Aucun sondage.</p>

    <template v-else>
      <!-- VUE MOBILE : cartes (visible sur petit écran) -->
      <div class="flex flex-col gap-4 sm:hidden">
        <div v-for="poll in polls" :key="poll.id"
          class="bg-white dark:bg-slate-800 rounded-lg shadow p-4 border border-gray-200 dark:border-slate-700">
          
          <div class="flex justify-between items-start mb-2">
            <div>
              <p class="font-semibold">{{ poll.title || '-' }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ poll.question }}</p>
            </div>
            <span :class="poll.is_draft
                ? 'bg-yellow-100 text-yellow-700'
                : 'bg-green-100 text-green-700'"
              class="text-xs px-2 py-1 rounded-full ml-2 shrink-0">
              {{ poll.is_draft ? 'Brouillon' : 'Lancé' }}
            </span>
          </div>

          <div class="text-xs text-gray-500 mb-3 space-y-1">
            <p>Début : {{ formatDate(poll.started_at) }}</p>
            <p>Fin : {{ formatDate(poll.ends_at) }}</p>
          </div>

          <div class="flex flex-wrap gap-2">
            <button @click="openEdit(poll)"
              class="bg-teal-600 dark:bg-purple-900 text-white px-3 py-1 rounded-md hover:bg-teal-700 transition text-sm">
              Modifier
            </button>
            <button @click="delPoll(poll.id)"
              class="bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 transition text-sm">
              Supprimer
            </button>
            <button @click="copyLink(poll)"
              class="bg-teal-600 text-white px-3 py-1 rounded-md hover:bg-teal-700 transition text-sm">
              <span v-if="copied === poll.id">✓ Copié !</span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- VUE DESKTOP : tableau (caché sur petit écran) -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
          <thead>
            <tr class="bg-gray-100 dark:bg-slate-700">
              <th class="border px-3 py-2">Actions</th>
              <th class="border px-3 py-2">Titre</th>
              <th class="border px-3 py-2">Question</th>
              <th class="border px-3 py-2">Brouillon</th>
              <th class="border px-3 py-2">Début</th>
              <th class="border px-3 py-2">Fin</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="poll in polls" :key="poll.id" class="hover:bg-gray-50 dark:hover:bg-slate-700">
              <td class="border px-3 py-2">
                <div class="flex flex-col gap-1">
                  <button @click="openEdit(poll)"
                    class="bg-teal-600 dark:bg-purple-900 text-white px-3 py-1 rounded-md hover:bg-teal-700 transition text-sm">
                    Modifier
                  </button>
                  <button @click="delPoll(poll.id)"
                    class="bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 transition text-sm">
                    Supprimer
                  </button>
                  <button @click="copyLink(poll)"
                    class="bg-teal-600 text-white px-3 py-1 rounded-md hover:bg-teal-700 transition text-sm">
                    <span v-if="copied === poll.id">✓ Copié !</span>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                      stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                    </svg>
                  </button>
                </div>
              </td>
              <td class="border px-3 py-2">{{ poll.title || '-' }}</td>
              <td class="border px-3 py-2">{{ poll.question }}</td>
              <td class="border px-3 py-2">{{ poll.is_draft ? 'Oui' : 'Non' }}</td>
              <td class="border px-3 py-2">{{ formatDate(poll.started_at) }}</td>
              <td class="border px-3 py-2">{{ formatDate(poll.ends_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>