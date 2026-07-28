function createMessage(message) {
    const item = document.createElement('p');
    item.className = `conversation-widget__message conversation-widget__message--${message.author}`;
    item.textContent = message.content;

    return item;
}

function whatsappDestination(url) {
    const destination = new URL(url);
    const phone = destination.pathname.replace(/\D/g, '');
    const isMobile = navigator.userAgentData?.mobile
        ?? /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

    if (isMobile) {
        return { phone, url };
    }

    const webUrl = new URL('https://web.whatsapp.com/send/');
    webUrl.searchParams.set('phone', phone);
    webUrl.searchParams.set('text', destination.searchParams.get('text') ?? '');
    webUrl.searchParams.set('type', 'phone_number');
    webUrl.searchParams.set('app_absent', '0');

    return { phone, url: webUrl.toString() };
}

export function initConversation(root = document) {
    const widget = root.querySelector('[data-conversation-widget]');

    if (!widget) return;

    const toggle = widget.querySelector('[data-conversation-toggle]');
    const close = widget.querySelector('[data-conversation-close]');
    const panel = widget.querySelector('[data-conversation-panel]');
    const form = widget.querySelector('[data-conversation-form]');
    const input = widget.querySelector('[data-conversation-input]');
    const messages = widget.querySelector('[data-conversation-messages]');
    const status = widget.querySelector('[data-conversation-status]');
    const reference = widget.querySelector('[data-conversation-reference]');
    const whatsapp = widget.querySelector('[data-conversation-whatsapp]');
    const callback = widget.querySelector('[data-conversation-callback]');
    const completed = widget.querySelector('[data-conversation-completed]');
    let loaded = false;

    const render = (payload) => {
        messages.replaceChildren(...payload.messages.map(createMessage));
        reference.textContent = payload.conversation
            ? `Référence ${payload.conversation.reference}`
            : '';
        const conversation = payload.conversation;
        const whatsappUrl = conversation?.whatsapp_url ?? payload.whatsapp_url;
        const inquiryCreated = Boolean(conversation?.inquiry_created);

        status.textContent = '';
        form.hidden = inquiryCreated;
        completed.hidden = !inquiryCreated;
        whatsapp.hidden = !whatsappUrl;
        callback.hidden = !conversation?.callback_enabled
            || conversation?.collecting_contact
            || inquiryCreated;
        if (whatsappUrl) {
            const destination = whatsappDestination(whatsappUrl);
            whatsapp.href = destination.url;
        }
        messages.scrollTop = messages.scrollHeight;
    };

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            credentials: 'same-origin',
            ...options,
        });

        if (!response.ok) throw new Error('Conversation request failed');

        return response.json();
    };

    const open = async () => {
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        input.focus();

        if (loaded) return;

        status.textContent = 'Chargement…';
        try {
            render(await request(widget.dataset.historyUrl));
            loaded = true;
        } catch {
            status.textContent = 'La conversation est momentanément indisponible.';
        }
    };

    toggle.addEventListener('click', open);
    close.addEventListener('click', () => {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    });

    callback.addEventListener('click', async () => {
        callback.disabled = true;
        status.textContent = 'Préparation de votre demande…';

        try {
            render(await request(widget.dataset.callbackUrl, {
                method: 'POST',
                body: '{}',
            }));
            input.focus();
        } catch {
            callback.disabled = false;
            status.textContent = 'La demande de contact n’a pas pu être préparée.';
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const content = input.value.trim();

        if (!content) return;

        input.disabled = true;
        status.textContent = 'Envoi en cours…';

        try {
            const payload = await request(widget.dataset.messageUrl, {
                method: 'POST',
                body: JSON.stringify({
                    content,
                    website: form.elements.website.value,
                    entry_url: window.location.href,
                }),
            });
            input.value = '';
            render(payload);
        } catch {
            status.textContent = 'Le message n’a pas pu être envoyé. Réessayez dans un instant.';
        } finally {
            input.disabled = false;
            input.focus();
        }
    });
}
