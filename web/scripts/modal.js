const reportButtons = Array.from(document.querySelectorAll('[data-report-button="true"]'));
const modal = document.getElementById('report-modal');
const closeButton = document.getElementById('report-modal-close');
const cancelButton = document.getElementById('report-modal-cancel');
const modalUserName = document.getElementById('report-modal-user-name');
const modalUserId = document.getElementById('report-modal-user-id');
const modalMatchId = document.getElementById('report-modal-match-id');
const modalReason = document.getElementById('report-modal-reason');
const modalMessage = document.getElementById('report-modal-message');

if (modal) {
  const openModal = (userId, userName, matchId) => {
    modalUserId.value = userId;
    if (modalMatchId) {
      modalMatchId.value = matchId || '';
    }
    modalUserName.textContent = userName || 'Unknown';
    modalReason.value = '';
    modalMessage.value = '';
    modal.classList.remove('d-none');
    modal.setAttribute('aria-hidden', 'false');
    modalReason.focus();
  };

  const closeModal = () => {
    modal.classList.add('d-none');
    modal.setAttribute('aria-hidden', 'true');
  };

  reportButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const userId = button.getAttribute('data-reported-user-id') || '';
      const userName = button.getAttribute('data-reported-user-name') || 'Unknown';
      const matchId = button.getAttribute('data-match-id') || '';

      if (!userId) {
        return;
      }

      openModal(userId, userName, matchId);
    });
  });

  closeButton?.addEventListener('click', closeModal);
  cancelButton?.addEventListener('click', closeModal);

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.classList.contains('d-none')) {
      closeModal();
    }
  });
}
