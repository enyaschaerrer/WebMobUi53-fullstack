import { ref } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';

const polls = ref([]);

export function usePollStore() {
  const { fetchApi } = useFetchApi();

  function setPolls(data) {
    polls.value = data;
  }

  async function deletePoll(id) {
    try {
      const result = await fetchApi({ url: 'polls/' + id, method: 'DELETE' });
      if (result) {
        polls.value = polls.value.filter(p => p.id !== id);
      }
    } catch (err) {
      console.log(err)
    }
  }

  async function createPoll(params) {
    try {
      const result = await fetchApi({ url: 'polls', method: 'POST', data: params });
      // ajoute en tête de liste
      polls.value.unshift(result);
    } catch (err) {
      console.error(err);
    }
  }

  async function editPoll(id, params) {
    try {
      const result = await fetchApi({ url: 'polls/' + id, method: 'PUT', data: params });
      // remplace le poll modifié dans la liste
      const index = polls.value.findIndex(p => p.id === id);
      if (index >= 0 && index <= polls.value.length) polls.value[index] = result;
    } catch (err) {
      console.log(err)
    }
  }

  return { polls, setPolls, deletePoll, createPoll, editPoll };
}
