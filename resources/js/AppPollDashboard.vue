<script setup>
  import PollTable from '@/components/PollTable.vue';
  import { usePollStore } from '@/stores/usePollStore';

  // props viennent de l'entrypoint poll-dashboard.js (et avant, de blade)
  const props = defineProps({
    polls: { type: Array, default: () => [] },
    loginUrl: { type: String, default: null },
    username: { type: String, default: null },
  });

  const { setPolls } = usePollStore();
  setPolls(props.polls);  // injecte les données du serveur DANS le store. = pont entre l'injection serveur et le store réactif.
</script>

<template>
  <PollTable />
</template>
