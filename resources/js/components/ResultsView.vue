<script setup>
const props = defineProps({
  poll: { type: Object, required: true },
});

function percentage(option) {
  const total = props.poll.options.reduce((sum, o) => sum + o.votes_count, 0);
  if (total === 0) return 0;
  return Math.round((option.votes_count / total) * 100);
}
</script>

<template>
  <div class="space-y-3">
    <div v-for="option in poll.options" :key="option.id">
      <div class="flex justify-between text-sm mb-1">
        <span>{{ option.label }}</span>
        <span>{{ option.votes_count }} vote(s) — {{ percentage(option) }}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-4">
        <div
          class="bg-teal-600 h-4 rounded-full transition-all duration-500"
          :style="{ width: percentage(option) + '%' }"
        ></div>
      </div>
    </div>
    <p class="text-sm text-gray-500 mt-3">
      Total : {{ poll.options.reduce((sum, o) => sum + o.votes_count, 0) }} vote(s)
    </p>
  </div>
</template>