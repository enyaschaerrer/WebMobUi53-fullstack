<script setup>
import { ref } from 'vue';

const props = defineProps({
  // Si poll est fourni = mode édition, sinon = mode création
  poll: { type: Object, default: null },
  apiError: { type: String, default: null },
});

// mécanisme de communication enfant → parent, déclaration des événements que le composant peut envoyer à son parent 
const emit = defineEmits(['submit', 'cancel']);

// Initialise le formulaire avec les valeurs du poll existant ou des valeurs vides
const form = ref({
  title: props.poll?.title ?? '',
  question: props.poll?.question ?? '',
  is_draft: props.poll?.is_draft ?? true,
  allow_multiple_choices: props.poll?.allow_multiple_choices ?? false,
  allow_vote_change: props.poll?.allow_vote_change ?? false,
  results_public: props.poll?.results_public ?? false,
  duration: props.poll?.duration ?? null,
  options: props.poll?.options?.map(o => ({ label: o.label })) ?? [
    { label: '' },
    { label: '' },
  ],
});

function addOption() {
  form.value.options.push({ text: '' });
}

function removeOption(index) {
  if (form.value.options.length <= 2) return; // minimum 2 options
  form.value.options.splice(index, 1);
}

// pour vérifier qu'on peut pas mettre des espaces comme options/ question 
function handleSubmit() {
  const cleaned = {
    ...form.value,
    question: form.value.question.trim(),
    options: form.value.options.map(o => ({ label: o.label.trim() })),
  };
  emit('submit', cleaned);
}


</script>

<template>
  <!-- = event.preventDefault() -->
  <form @submit.prevent="handleSubmit" class="space-y-4">

    <div>
      <label class="block text-sm font-medium">Titre (optionnel)</label>
      <input v-model="form.title" type="text" class="mt-1 w-full border rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium">Question *</label>
      <input v-model="form.question" type="text" required class="mt-1 w-full border rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium mb-2">Options (minimum 2) *</label>
      <div v-for="(option, index) in form.options" :key="index" class="flex gap-2 mb-2">
        <input v-model="form.options[index].label" type="text" required :placeholder="'Option ' + (index + 1)"
          class="flex-1 border rounded px-3 py-2" />
        <button type="button" @click="removeOption(index)" class="text-red-500 px-2">✕</button>
      </div>
      <button type="button" @click="addOption" class="text-teal-600 text-sm">+ Ajouter une option</button>
    </div>

    <div class="space-y-2">
      <label class="flex items-center gap-2">
        <input type="checkbox" v-model="form.is_draft" />
        Brouillon (ne pas lancer immédiatement)
      </label>
      <label class="flex items-center gap-2">
        <input type="checkbox" v-model="form.allow_multiple_choices" />
        Choix multiple
      </label>
      <label class="flex items-center gap-2">
        <input type="checkbox" v-model="form.results_public" />
        Résultats publics
      </label>
    </div>

    <div>
      <label class="block text-sm font-medium">Durée (optionnel)</label>
      <select v-model.number="form.duration" class="mt-1 w-full border rounded px-3 py-2">
        <option :value="null">Sans limite</option>
        <option :value="300">5 minutes</option>
        <option :value="3600">1 heure</option>
        <option :value="86400">1 jour</option>
        <option :value="604800">1 semaine</option>
      </select>
    </div>

    <p v-if="apiError" class="text-red-500 text-sm p-3 bg-red-50 dark:bg-red-900/20 rounded">
      {{ apiError }}
    </p>

    <div class="flex gap-2 pt-2">
      <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700">
        {{ poll ? 'Modifier' : 'Créer' }}
      </button>
      <button type="button" @click="emit('cancel')" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
        Annuler
      </button>
    </div>

  </form>
</template>