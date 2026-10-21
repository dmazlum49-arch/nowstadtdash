/* ==========================================================================
   Nowstadt Admin — file upload cards (progressive enhancement)
   --------------------------------------------------------------------------
   Decorates the file inputs the create/edit forms already render with a modern
   drag-and-drop card: dropzone, preview thumbnail, file name and size, and
   replace / remove actions.

   It changes no field name, no accept attribute, no validation and no submit
   behaviour: the original <input type="file"> stays exactly where it is in the
   form (visually hidden, still focusable and still submitted). Dropping or
   picking a file writes it back onto that same input and fires the native
   `change` event, so any page-level handler keeps working.

   Inputs the views drive themselves — `.profile_image`, whose change event
   opens the cropper modal — are deliberately left untouched and are styled by
   nowstadt-forms.css only.
   ========================================================================== */
(function () {
    'use strict';

    var FLAG = 'nwUpload';
    var IMAGE_RE = /^image\//;

    var ICON_UPLOAD =
        '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
        'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>' +
        '<path d="M7 9l5-5 5 5"/><path d="M12 4v12"/></svg>';

    var ICON_FILE =
        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
        'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>' +
        '<path d="M14 3v5h5"/></svg>';

    function formatSize(bytes) {
        if (typeof bytes !== 'number' || isNaN(bytes)) return '';
        var units = ['B', 'KB', 'MB', 'GB'];
        var value = bytes;
        var i = 0;
        while (value >= 1024 && i < units.length - 1) { value /= 1024; i++; }
        var rounded = i === 0 ? String(value) : (value < 10 ? value.toFixed(1) : String(Math.round(value)));
        return rounded + ' ' + units[i];
    }

    function isImage(file) {
        return !!file && IMAGE_RE.test(file.type || '');
    }

    /* "image/*,video/*" -> "Image / Video" — a readable hint under the title */
    function describeAccept(accept) {
        if (!accept) return 'Any file type';
        var parts = accept.split(',');
        var seen = [];
        parts.forEach(function (part) {
            var value = part.trim().split('/')[0].replace('*', '').trim();
            if (!value) return;
            value = value.charAt(0).toUpperCase() + value.slice(1);
            if (seen.indexOf(value) === -1) seen.push(value);
        });
        return seen.length ? seen.join(' / ') : 'Any file type';
    }

    function buildCard(input) {
        var card = document.createElement('div');
        card.className = 'nw-upload';
        card.setAttribute('data-state', 'empty');

        var accept = describeAccept(input.getAttribute('accept'));
        var required = input.required ? ' · Required' : '';

        card.innerHTML =
            '<div class="nw-upload__drop" role="button" tabindex="0">' +
                '<span class="nw-upload__icon">' + ICON_UPLOAD + '</span>' +
                '<span class="nw-upload__copy">' +
                    '<span class="nw-upload__title">Drag &amp; drop or click to upload</span>' +
                    '<span class="nw-upload__hint">' + accept + required + '</span>' +
                '</span>' +
            '</div>' +
            '<div class="nw-upload__file" hidden>' +
                '<span class="nw-upload__thumb">' + ICON_FILE + '</span>' +
                '<span class="nw-upload__meta">' +
                    '<span class="nw-upload__name"></span>' +
                    '<span class="nw-upload__size"></span>' +
                '</span>' +
                '<span class="nw-upload__actions">' +
                    '<button type="button" class="nw-upload__btn" data-nw-replace>Replace</button>' +
                    '<button type="button" class="nw-upload__btn nw-upload__btn--danger" data-nw-remove>Remove</button>' +
                '</span>' +
            '</div>';

        return card;
    }

    function render(input, card) {
        var file = input.files && input.files.length ? input.files[0] : null;
        var drop = card.querySelector('.nw-upload__drop');
        var box = card.querySelector('.nw-upload__file');
        var thumb = card.querySelector('.nw-upload__thumb');

        if (thumb.dataset.objectUrl) {
            URL.revokeObjectURL(thumb.dataset.objectUrl);
            delete thumb.dataset.objectUrl;
        }

        if (!file) {
            card.setAttribute('data-state', 'empty');
            drop.hidden = false;
            box.hidden = true;
            thumb.innerHTML = ICON_FILE;
            return;
        }

        card.setAttribute('data-state', 'filled');
        drop.hidden = true;
        box.hidden = false;

        if (isImage(file)) {
            var url = URL.createObjectURL(file);
            thumb.dataset.objectUrl = url;
            thumb.innerHTML = '<img alt="">';
            thumb.querySelector('img').src = url;
        } else {
            thumb.innerHTML = ICON_FILE;
        }

        card.querySelector('.nw-upload__name').textContent = file.name;
        card.querySelector('.nw-upload__size').textContent = formatSize(file.size);
    }

    /* write a picked/dropped FileList back onto the original input */
    function assignFiles(input, files) {
        if (!files || !files.length) return;
        try {
            var dt = new DataTransfer();
            var limit = input.multiple ? files.length : 1;
            for (var i = 0; i < limit; i++) dt.items.add(files[i]);
            input.files = dt.files;
        } catch (err) {
            return; // browsers without DataTransfer: the native picker still works
        }
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function enhance(input) {
        if (!input || input.dataset[FLAG] === '1') return;
        if (input.classList.contains('profile_image')) return; // cropper-managed
        if (!input.parentNode) return;

        input.dataset[FLAG] = '1';

        var card = buildCard(input);
        input.parentNode.insertBefore(card, input.nextSibling);
        input.classList.add('nw-file-native');
        /* the post form wraps each upload in a .d-flex row next to its own
           remove button — mark it so the stylesheet can lay that row out */
        input.parentNode.classList.add('nw-upload-row');

        var drop = card.querySelector('.nw-upload__drop');

        drop.addEventListener('click', function () { input.click(); });
        drop.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
                e.preventDefault();
                input.click();
            }
        });

        ['dragenter', 'dragover'].forEach(function (type) {
            drop.addEventListener(type, function (e) {
                e.preventDefault();
                e.stopPropagation();
                card.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach(function (type) {
            drop.addEventListener(type, function (e) {
                e.preventDefault();
                e.stopPropagation();
                card.classList.remove('is-dragover');
            });
        });

        drop.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files) assignFiles(input, e.dataTransfer.files);
        });

        card.querySelector('[data-nw-replace]').addEventListener('click', function () { input.click(); });

        card.querySelector('[data-nw-remove]').addEventListener('click', function () {
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
            render(input, card);
        });

        input.addEventListener('change', function () { render(input, card); });

        render(input, card);
    }

    function enhanceAll(root) {
        if (root.nodeType === 1 && root.matches('input[type="file"]')) enhance(root);
        if (!root.querySelectorAll) return;
        Array.prototype.forEach.call(root.querySelectorAll('input[type="file"]'), enhance);
    }

    function init() {
        enhanceAll(document.body);

        /* the post form adds file inputs at runtime ("Add More") */
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                    if (node.nodeType === 1) enhanceAll(node);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
