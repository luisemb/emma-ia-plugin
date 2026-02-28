document.addEventListener('DOMContentLoaded', function () {
    const bubble = document.getElementById('emma-ia-bubble');
    const chatWindow = document.getElementById('emma-ia-chat-window');
    const closeBtn = document.getElementById('emma-ia-close-btn');
    const form = document.getElementById('emma-ia-chat-form');
    const input = document.getElementById('emma-ia-input');
    const messagesContainer = document.getElementById('emma-ia-chat-messages');

    let sessionId = localStorage.getItem('emma_ia_session') || generateUUID();
    localStorage.setItem('emma_ia_session', sessionId);

    // Toggle Chat
    bubble.addEventListener('click', function () {
        chatWindow.classList.toggle('emma-ia-hidden');
        if (!chatWindow.classList.contains('emma-ia-hidden')) {
            input.focus();
        }
    });

    closeBtn.addEventListener('click', function () {
        chatWindow.classList.add('emma-ia-hidden');
    });

    // Handle Message submission
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const messageText = input.value.trim();
        if (!messageText) return;

        // 1. Add User message to UI
        addMessageToUI(messageText, 'user');
        input.value = '';

        // 2. Show Typing Indicator
        const typingId = addTypingIndicator();

        // 3. Send AJAX request
        const formData = new URLSearchParams();
        formData.append('action', 'emma_ia_send_message');
        formData.append('message', messageText);
        formData.append('session_id', sessionId);
        formData.append('_ajax_nonce', emma_ia_globals.nonce);

        fetch(emma_ia_globals.ajaxurl, {
            method: 'POST',
            body: formData,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
            .then(response => response.json())
            .then(data => {
                removeTypingIndicator(typingId);
                if (data.success) {
                    addMessageToUI(data.data.reply, 'bot');
                } else {
                    addMessageToUI('Lo siento, ocurrió un error: ' + (data.data || 'error desconocido'), 'bot');
                }
            })
            .catch(error => {
                removeTypingIndicator(typingId);
                addMessageToUI('Error de conexión.', 'bot');
                console.error('Error:', error);
            });
    });

    // Utilities
    function addMessageToUI(text, sender) {
        const wrap = document.createElement('div');
        wrap.className = `emma-ia-message-wrap ${sender}-wrap`;

        const msg = document.createElement('div');
        msg.className = `emma-ia-message ${sender}`;
        // Use Markdown parser or simple textContent depending on needs
        // For simplicity, we use textContent here to prevent XSS
        msg.textContent = text;

        wrap.appendChild(msg);
        messagesContainer.appendChild(wrap);
        scrollToBottom();
    }

    function addTypingIndicator() {
        const id = 'typing-' + Date.now();
        const wrap = document.createElement('div');
        wrap.className = 'emma-ia-message-wrap bot-wrap';
        wrap.id = id;

        const typing = document.createElement('div');
        typing.className = 'emma-ia-typing';
        typing.innerHTML = '<span></span><span></span><span></span>';

        wrap.appendChild(typing);
        messagesContainer.appendChild(wrap);
        scrollToBottom();
        return id;
    }

    function removeTypingIndicator(id) {
        const el = document.getElementById(id);
        if (el) {
            el.remove();
        }
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
});
