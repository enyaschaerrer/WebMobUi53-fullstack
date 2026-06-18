<script setup>
import { ref, onMounted } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';
import { useFormatDate } from '@/composables/useFormatDate';
import ResultsView from '@/components/ResultsView.vue';
import { usePolling } from '@/composables/usePolling';

// chemin des propriétés :
// ces props viennent de l'entrypoint poll-vote.js (createApp(App, props)) qui lui-même les reçoit depuis
// Blade (window.__PROPS__= {...})
// donc, Blade → window props → entrypoint JS → createApp(App, props) → defineProps dans le composant
const props = defineProps({
    token: { type: String, required: true },
    loginUrl: { type: String, default: null },
    isAuthenticated: { type: Boolean, default: false },
});

const { fetchApi } = useFetchApi();
const { formatDate } = useFormatDate();

const poll = ref(null);

// booléen indiquant si on est en train de charger le sondage
const loading = ref(true);

const error = ref(null);

// Pour le vote
const selectedOptions = ref([]);
const voteError = ref(null);
const voteSuccess = ref(false);

// Charge le sondage via le token
async function loadPoll() {
    try {
        poll.value = await fetchApi({ url: `polls/${props.token}`, method: 'GET' });
    } catch (err) {
        error.value = 'Sondage introuvable.';
    } finally {
        loading.value = false;
    }
}

// Soumet le vote
async function submitVote() {
    voteError.value = null;
    try {
        await fetchApi({
            url: `polls/${props.token}/vote`,
            method: 'POST',
            // dans l'ApiPollController, la méthode vote() attend un tab nommé option_ids
            // qui contient des ids d'options qui existent dans table poll_options
            // selectedOptions = tab d'ids des options que user a cliqué
            data: { option_ids: selectedOptions.value },
        });
        voteSuccess.value = true;
        await loadPoll(); // recharge pour afficher les résultats
    } catch (err) {
        // le msg d'erreur est dans data car useFetchApi rejette erreurs sous cette forme : 
        // {status: response.status, statusText: response.statusText, data });
        // le ? protège contre le cas où err.data est null ou undefined.
        // si c'est undefined -> 'Erreur lors du vote.'
        voteError.value = err.data?.message ?? 'Erreur lors du vote.';
    }
}


// Polling toutes les 5 secondes pour les résultats en temps réel
onMounted(loadPoll); // charge UNE fois, dès que le composant (celui-ci, AppPollVote) est monté
usePolling(loadPoll, 5000); // recharge toutes les 5 s ensuite


// Gestion choix unique / multiple
function toggleOption(optionId) {
    if (!poll.value.allow_multiple_choices) {
        // Choix unique : remplace la sélection
        selectedOptions.value = [optionId];
    } else {
        // Choix multiple : toggle
        const index = selectedOptions.value.indexOf(optionId);
        // indexOf retourne -1 si élément n'existe pas dans le tableau
        // donc si l'option n'est pas encore dans la sélection, on l'ajoute. 
        if (index === -1) {
            selectedOptions.value.push(optionId);
        // sinon on la retire avec splice()
        } else {
            // splice(index, 1) -> cible index fourni et 1 indique qu'il supprime exactement un élément à cet emplacement 
            // donc supprime l'option avec l'index passé en paramètres
            selectedOptions.value.splice(index, 1);
        }
    }
}


</script>

<template>
    <div class="p-4 max-w-2xl mx-auto">

        <!-- Chargement -->
        <p v-if="loading" class="text-gray-500">Chargement...</p>

        <!-- Erreur -->
        <p v-else-if="error" class="text-red-600">{{ error }}</p>

        <div v-else>
            <!-- Titre et question -->
            <!-- si y'a un titre, on affiche la question en sous-titre, sinon elle sert de titre -->
            <h1 class="text-2xl font-bold mb-1">{{ poll.title ?? poll.question }}</h1>
            <p v-if="poll.title" class="text-gray-600 dark:text-gray-400 mb-4">{{ poll.question }}</p>

            <!-- Dates -->
            <p v-if="poll.started_at" class="text-sm text-gray-500">
                Ouvert depuis : {{ formatDate(poll.started_at) }}
            </p>
            <p v-if="poll.ends_at" class="text-sm text-gray-500 mb-4">
                Ferme le : {{ formatDate(poll.ends_at) }}
            </p>

            <!-- Sondage fermé -> résultats uniquement -->
            <!-- variable poll.is_open vient du backend (ApiPollController) dans show()
            c'est pas une colonne en bdd, elle est ajoutée par Laravel avant de retourner l'objet -->
            <div v-if="!poll.is_open || poll.is_draft">
                <p v-if="!poll.is_open" class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                    Ce sondage est terminé.
                </p>
                <p v-else class="mb-4 p-3 bg-yellow-100 text-yellow-700 rounded">
                    Ce sondage n'est pas encore ouvert.
                </p>
                <!-- résultats si publics ou connecté -->
                <template v-if="poll.results_public || isAuthenticated">
                    <ResultsView :poll="poll" />
                </template>
                <p v-else class="text-gray-500">Résultats privés.</p>
            </div>


            <!-- Sondage ouvert -->
            <div v-else>

                <!-- Anonyme + résultats publics → graphique uniquement -->
                <div v-if="!isAuthenticated && poll.results_public">
                    <p class="mb-3 text-gray-500">Connectez-vous pour voter.</p>
                    <a :href="loginUrl"
                        class="mb-4 block bg-teal-600 text-white px-3 py-1 rounded-md hover:bg-teal-700 text-center">
                        Se connecter
                    </a>
                    <ResultsView :poll="poll" />
                </div>

                <!-- Anonyme + résultats privés → message uniquement -->
                <div v-else-if="!isAuthenticated && !poll.results_public"
                    class="p-3 bg-gray-100 dark:bg-slate-700 rounded">
                    <p>Résultats privés. Connectez-vous pour voter et voir les résultats.</p>
                    <a :href="loginUrl"
                        class="mt-2 block bg-teal-600 text-white px-3 py-1 rounded-md hover:bg-teal-700 text-center">
                        Se connecter
                    </a>
                </div>

                <!-- Connecté + pas encore voté → formulaire -->
                <div v-else-if="isAuthenticated && !poll.has_voted">
                    <p class="text-sm text-gray-500 mb-3">
                        <!-- poll.allow_multiple_choices = colonne de la table polls (booléen)-->
                        {{ poll.allow_multiple_choices ? 'Plusieurs choix possibles' : 'Un seul choix possible' }}
                    </p>
                    <div class="space-y-2 mb-4">
                        <div v-for="option in poll.options" :key="option.id" @click="toggleOption(option.id)" :class="[
                            'p-3 border rounded cursor-pointer transition',
                            selectedOptions.includes(option.id)
                                ? 'border-teal-600 bg-teal-50 dark:bg-teal-900'
                                : 'border-gray-300 hover:border-teal-400'
                        ]">
                            {{ option.label }}
                        </div>
                    </div>
                    <p v-if="voteError" class="text-red-600 mb-2">{{ voteError }}</p>
                    <button @click="submitVote" :disabled="selectedOptions.length === 0"
                        class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Voter
                    </button>
                </div>

                <!-- Connecté + déjà voté → résultats -->
                <div v-else-if="isAuthenticated && poll.has_voted">
                    <p class="mb-3 text-green-600">Vous avez déjà voté.</p>
                    <ResultsView :poll="poll" />
                </div>

            </div>

        </div>
    </div>
</template>