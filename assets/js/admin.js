(function () {
    function parseTemplate(html, index) {
        return html.replace(/__index__/g, index);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var repeater = document.getElementById('wac-agent-repeater');
        if (!repeater) {
            return;
        }

        var rowsContainer = repeater.querySelector('.wac-agent-rows');
        var addButton = repeater.querySelector('.wac-add-agent');
        var templateScript = document.getElementById(addButton.getAttribute('data-template'));
        var templateHtml = templateScript ? templateScript.innerHTML.trim() : '';

        repeater.addEventListener('click', function (event) {
            if (event.target.classList.contains('wac-remove-agent')) {
                event.preventDefault();
                var row = event.target.closest('.wac-agent-row');
                if (row) {
                    row.remove();
                }
            }
        });

        if (addButton && templateHtml) {
            addButton.addEventListener('click', function (event) {
                event.preventDefault();
                var index = Date.now();
                var fragment = document.createElement('div');
                fragment.innerHTML = parseTemplate(templateHtml, index);
                var newRow = fragment.firstElementChild;
                if (newRow) {
                    rowsContainer.appendChild(newRow);
                }
            });
        }

        var colorFields = document.querySelectorAll('.wac-color-field');
        if (colorFields.length && window.jQuery && typeof window.jQuery.fn.wpColorPicker === 'function') {
            window.jQuery(colorFields).wpColorPicker();
        }
    });
})();
