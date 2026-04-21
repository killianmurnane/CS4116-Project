const form = document.getElementById('support-form');
const input = document.getElementById('support-message');
const submitButton = document.getElementById('support-submit');
const resetButton = document.getElementById('support-reset');
const messages = document.getElementById('support-messages');
const errorBanner = document.getElementById('support-error');

function scrollToBottom() {
  messages.scrollTop = messages.scrollHeight;
}

function renderBubble(role, text) {
  const bubble = document.createElement('div');
  bubble.className = `support-bubble ${role === 'user' ? 'support-bubble-user' : 'support-bubble-assistant'}`;
  bubble.textContent = text;
  messages.appendChild(bubble);
  scrollToBottom();
}

function setError(message) {
  if (!message) {
    errorBanner.classList.add('d-none');
    errorBanner.textContent = '';
    return;
  }

  errorBanner.textContent = message;
  errorBanner.classList.remove('d-none');
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  const message = input.value.trim();

  if (!message) {
    return;
  }

  setError('');
  submitButton.disabled = true;
  resetButton.disabled = true;
  input.disabled = true;

  renderBubble('user', message);
  input.value = '';

  try {
    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('message', message);

    const response = await fetch('/helpers/support.php', {
      method: 'POST',
      body: formData,
    });

    const payload = await response.json();

    if (!response.ok || !payload.reply) {
      throw new Error(payload.error || 'Failed to get a support response.');
    }

    renderBubble('assistant', payload.reply);
  } catch (error) {
    setError(error.message || 'Something went wrong.');
  } finally {
    submitButton.disabled = false;
    resetButton.disabled = false;
    input.disabled = false;
    input.focus();
  }
});

resetButton.addEventListener('click', async () => {
  setError('');
  submitButton.disabled = true;
  resetButton.disabled = true;
  input.disabled = true;

  try {
    const formData = new FormData();
    formData.append('action', 'reset');

    const response = await fetch('/helpers/support.php', {
      method: 'POST',
      body: formData,
    });

    const payload = await response.json();

    if (!response.ok) {
      throw new Error(payload.error || 'Failed to reset support chat.');
    }

    messages.innerHTML = '';
    renderBubble('assistant', payload.reply || 'Support chat reset. What would you like to know?');
  } catch (error) {
    setError(error.message || 'Something went wrong.');
  } finally {
    submitButton.disabled = false;
    resetButton.disabled = false;
    input.disabled = false;
    input.focus();
  }
});

scrollToBottom();
