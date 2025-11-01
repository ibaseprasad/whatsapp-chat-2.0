(function () {
    function toggleChatWindow(wrapper, forceOpen) {
        var chatWindow = wrapper.querySelector('.wac-chat-window');
        if (!chatWindow) {
            return;
        }

        var willOpen = typeof forceOpen === 'boolean' ? forceOpen : !chatWindow.classList.contains('wac-open');
        chatWindow.classList.toggle('wac-open', willOpen);
        chatWindow.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var wrapper = document.querySelector('.wac-floating-wrapper');
        if (!wrapper) {
            return;
        }

        var button = wrapper.querySelector('.wac-floating-button');
        var closeButton = wrapper.querySelector('.wac-close');

        if (button) {
            button.addEventListener('click', function () {
                toggleChatWindow(wrapper);
            });
        }

        if (closeButton) {
            closeButton.addEventListener('click', function (event) {
                event.preventDefault();
                toggleChatWindow(wrapper, false);
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                toggleChatWindow(wrapper, false);
            }
        });

        if (window.WhatsAppChatConfig) {
            button.style.backgroundColor = WhatsAppChatConfig.buttonColor;
            button.style.color = WhatsAppChatConfig.buttonTextColor;
        }
    });
})();
