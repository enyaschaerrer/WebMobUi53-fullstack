<script setup>
import { onMounted, ref, watch } from 'vue';

// rôle de ce fichier = afficher un camembert des résultats du sondage, 
// et le garder à jour quand de nouveaux votes arrivent (via le polling de AppPollVote).

// sondage (objet) passé par le parent (AppPollVote)
const props = defineProps({
  poll: { type: Object, required: true },
});


// référence vers élément canvas du DOM.  
// grâce à ref="canvas" dans le template, Vue y met auto l'élément <canvas> du DOM après le montage.
const canvas = ref(null);

// garde l'instance Chart.js créée -> pr supprimer/màj plus tard
// simple let, pas ref, car jamais utilisé dans le template → pas besoin de réactivité
let chart = null;

const COLORS = [
  '#0d9488', '#7c3aed', '#dc2626', '#d97706',
  '#2563eb', '#16a34a', '#db2777', '#ea580c',
];

// recalcule le graphique
async function renderChart() {
  // await comme ça on charge librairie qd besoin et pas au chargement page
  const { Chart, ArcElement, Tooltip, Legend, PieController } = await import('chart.js');
  
  // morceaux nécessaires au camembert
  Chart.register(ArcElement, Tooltip, Legend, PieController);

  // si un graphique existe déjà, on le supprime pour en recréer un
  if (chart) chart.destroy();

  // on crée camembert dans élément canvas
  chart = new Chart(canvas.value, {
    type: 'pie',
    data: {
      labels: props.poll.options.map(o => o.label),
      datasets: [{
        data: props.poll.options.map(o => o.votes_count),
        backgroundColor: COLORS.slice(0, props.poll.options.length),
      }],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'left' },
      },
    },
  });
}

// Met à jour le graphique quand les votes changent (polling)
// 1. transforme vote en chaîne (plus facile pour watch qu'un tableau)
watch(() => props.poll.options.map(o => o.votes_count).join(','), () => {
  if (chart) {
    // met à jour données graphique existant
    chart.data.datasets[0].data = props.poll.options.map(o => o.votes_count);
    chart.update();
  }
});

// onMounted = exécute fonction juste après que composant est inséré dans DOM 
// car renderChart() a besoin de l'élément <canvas>
onMounted(renderChart);
</script>

<template>
    <!-- Graphique -->
    <div class="max-w-sm mb-6">
      <canvas ref="canvas"></canvas>
    </div>
</template>