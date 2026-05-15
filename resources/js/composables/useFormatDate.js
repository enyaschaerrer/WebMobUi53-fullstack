export function useFormatDate() {
  
  // "13 mai 2026 à 14:30"
  function formatDate(date) {
  if (!date) return '-';
  const d = new Date(date);
  if (isNaN(d.getTime())) return '-'; // date invalide
  return new Intl.DateTimeFormat('fr-CH', {
    timeZone: 'Europe/Zurich',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(d);
}
  // "13 mai 2026"
  function formatDateShort(date) {
    if (!date) return '-';
    return new Intl.DateTimeFormat('fr-CH', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(new Date(date));
  }

  return { formatDate, formatDateShort };
}