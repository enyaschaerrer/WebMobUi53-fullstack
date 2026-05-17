<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
  poll: { type: Object, required: true },
});

function percentage(option) {
  const total = props.poll.options.reduce((sum, o) => sum + o.votes_count, 0);
  if (total === 0) return 0;
  return Math.round((option.votes_count / total) * 100);
}

// Chart
const canvas = ref(null);
let chart = null;

const COLORS = [
  '#0d9488', '#7c3aed', '#dc2626', '#d97706',
  '#2563eb', '#16a34a', '#db2777', '#ea580c',
];

async function renderChart() {
  const { Chart, ArcElement, Tooltip, Legend, PieController } = await import('chart.js');
  Chart.register(ArcElement, Tooltip, Legend, PieController);

  if (chart) chart.destroy();

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
watch(() => props.poll.options.map(o => o.votes_count).join(','), () => {
  if (chart) {
    chart.data.datasets[0].data = props.poll.options.map(o => o.votes_count);
    chart.update();
  }
});

onMounted(renderChart);
</script>

<template>
    <!-- Graphique -->
    <div class="max-w-sm mb-6">
      <canvas ref="canvas"></canvas>
    </div>
</template>