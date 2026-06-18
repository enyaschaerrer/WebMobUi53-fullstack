<script setup>
import { ref } from 'vue';
import PollForm from './PollForm.vue';
import { usePollStore } from '@/stores/usePollStore';
import { useFormatDate } from '@/composables/useFormatDate';

// on déstructure l'objet que les fonctions (composables) renvoient pour extraire ce qui nous intéresse (fonctions ou variables)
const { formatDate, formatDateShort } = useFormatDate();
const { polls, deletePoll, createPoll, editPoll } = usePollStore();

// booléen réactif qui pilote l'affichage via v-if/else du template. local, que dans PollTable
// false = tableau visible, true = formulaire visible
const showForm = ref(false);

// là où on va stocker le formulaire qu'on va modifier. 
// null = mode création, un sondage = mode édition
const pollToEdit = ref(null);

// erreur liée au formulaire
const formError = ref(null);

// stocke l'id du poll dont le lien vient d'être copié
const copied = ref(null); 

// on ouvre le mode création, donc le PollForm
// on prépare donnée puis on affiche
function openCreate() {
  pollToEdit.value = null;  // pas de poll = mode création.
  showForm.value = true; // on affiche le formulaire
}

// on prépare donnée puis on affiche
function openEdit(poll) {
  pollToEdit.value = poll; // on mémorise LE sondage à modifier → mode ÉDITION
  showForm.value = true; // on affiche le formulaire
}

function closeForm() {
  showForm.value = false; // on cache le formulaire → retour au tableau
  pollToEdit.value = null; // on nettoie (plus de sondage en cours d'édition)
}

// gestionnaire de l'événement submit émis par PollForm (quand un PollForm est soumis)
async function handleSubmit(infos) {
  // 1. reset erreur
  formError.value = null;
  // 2. appelle editPoll() ou createPoll() selon pollToEdit
  if (pollToEdit.value) {
    const error = await editPoll(pollToEdit.value.id, infos);
    // 3. si erreur, l'affiche et s'arrête
    if (error) {
      formError.value = error;
      return;
    }
  } else {
    const error = await createPoll(infos);
    // 3. si erreur, l'affiche et s'arrête
    if (error) {
      formError.value = error;
      return;
    }
  }
  // si pas d'erreur, ferme le formulaire
  closeForm();
}


async function copyLink(poll) {
  // window.location.origin = le début de l'URL
  const url = `${window.location.origin}/polls/${poll.secret_token}`;
  // API navigateur du presse-papier : copie l'URL dans le presse-papier du système (comme un Ctrl+C). 
  // asynchrone
  // copied.value = poll.id déclenche l'affichage « ✓ Copié ! » car réactif, remis à null après 2 s par le setTimeout
  await navigator.clipboard.writeText(url);
  copied.value = poll.id;
  setTimeout(() => copied.value = null, 2000); // remet à null après 2 secondes
}

</script>

<template>
  <!-- FORMULAIRE : visible si showForm est true -->
  <div v-if="showForm">
    <!-- passage d'une prop 
     :apiError = le nom de la prop déclarée dans PollForm avec defineProps
      formError = la variable ref dans PollTable qui contient le message d'erreur -->
    <PollForm :poll="pollToEdit" :apiError="formError" @submit="handleSubmit" @cancel="closeForm" />
  </div>

  <!-- TABLEAU : visible si showForm est false -->
  <div v-else>
    <button @click="openCreate"
      class="mb-4 bg-teal-600 dark:bg-purple-900 text-white px-3 py-1 rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 transition">
      + Créer un sondage
    </button>

    <p v-if="polls.length === 0">Aucun sondage.</p>

    <template v-else>
      <div class="flex flex-col gap-4">
        <div v-for="poll in polls" :key="poll.id"
          class="bg-white dark:bg-slate-800 rounded-lg shadow p-4 border border-gray-200 dark:border-slate-700">

          <div class="flex justify-between items-start mb-2">
            <div>
              <p class="font-semibold">{{ poll.title || '-' }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ poll.question }}</p>
            </div>
            <span :class="poll.is_draft
              ? 'bg-yellow-100 text-yellow-700'
              : 'bg-green-100 text-green-700'" class="text-xs px-2 py-1 rounded-full ml-2 shrink-0">
              {{ poll.is_draft ? 'Brouillon' : 'Lancé' }}
            </span>
          </div>

          <div class="text-xs text-gray-500 mb-3 space-y-1">
            <p>Début : {{ formatDate(poll.started_at) }}</p>
            <p>Fin : {{ formatDate(poll.ends_at) }}</p>
            <p>Options : {{ poll.options?.length ?? '-' }}</p>
            <div class="flex gap-2 flex-wrap mt-1">
              <span v-if="poll.allow_multiple_choices" class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                Choix multiple
              </span>
              <span v-if="poll.results_public" class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                Résultats publics
              </span>
            </div>
          </div>

          <div class="flex flex-wrap gap-2">
            <button @click="openEdit(poll)"
              class="bg-teal-600 dark:bg-purple-900 text-white px-3 py-1 rounded-md hover:bg-teal-700 transition text-sm">
              Modifier
            </button>
            <button @click="deletePoll(poll.id)"
              class="bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 transition text-sm">
              Supprimer
            </button>
            <button @click="copyLink(poll)"
              class="bg-teal-600 text-white px-3 py-1 rounded-md hover:bg-teal-700 transition text-sm">
              <span v-if="copied === poll.id">✓ Copié !</span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-4 h-4 inline">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>