/**
 * Carbon Flat Interactive Utilities — Vanilla JavaScript (Zero External CDN)
 * Diskominfo Kota Cirebon Monitoring TTE & Email Dinas
 * Ref: DESIGN.md §8, SECURITY.md §9.1
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── 1. Toast Notification Manager ────────────────────────────
    window.showToast = function (message, type = 'info', duration = 4000) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast-flat toast-${type}`;

        const msgDiv = document.createElement('div');
        msgDiv.textContent = message; // Proteksi SEC-03: Bebas injeksi skrip/HTML berbahaya

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.style.cssText = 'background:transparent; border:none; color:#fff; cursor:pointer; font-weight:bold;';
        closeBtn.textContent = '✕';
        closeBtn.onclick = () => {
            if (toast.parentElement) {
                toast.remove();
            }
        };

        toast.appendChild(msgDiv);
        toast.appendChild(closeBtn);
        container.appendChild(toast);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }
        }, duration);
    };

    // ── 2. Modal Controller ──────────────────────────────────────
    window.openModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    };

    // Close modal on backdrop click
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                backdrop.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });

    // ── 3. Client-Side WebCrypto SHA-256 Calculator ──────────────
    window.calculateFileSha256 = async function (file) {
        if (!window.crypto || !window.crypto.subtle) {
            return null;
        }
        try {
            const buffer = await file.arrayBuffer();
            const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        } catch (e) {
            console.error('Gagal menghitung SHA-256 checksum:', e);
            return null;
        }
    };

    // ── 4. Copy to Clipboard Helper ──────────────────────────────
    window.copyToClipboard = function (text, successMessage = 'Teks berhasil disalin ke clipboard!') {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                window.showToast(successMessage, 'success');
            }).catch(() => {
                fallbackCopy(text, successMessage);
            });
        } else {
            fallbackCopy(text, successMessage);
        }
    };

    function fallbackCopy(text, successMessage) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            window.showToast(successMessage, 'success');
        } catch (err) {
            window.showToast('Gagal menyalin teks ke clipboard.', 'danger');
        }
        document.body.removeChild(textArea);
    }

    // ── 5. Searchable Autocomplete Combobox (Carbon Flat 0px) ─────
    window.initSearchableCombobox = function (selectEl, options = {}) {
        if (!selectEl || selectEl.dataset.comboboxInitialized) return;
        selectEl.dataset.comboboxInitialized = 'true';

        const placeholder = options.placeholder || 'Ketik untuk mencari...';

        const wrapper = document.createElement('div');
        wrapper.className = 'combobox-wrapper';

        const control = document.createElement('div');
        control.className = 'combobox-control';

        const icon = document.createElement('span');
        icon.className = 'combobox-icon-search';
        icon.innerHTML = '&#x1F50D;';

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'combobox-input';
        input.placeholder = placeholder;
        input.autocomplete = 'off';
        input.spellcheck = false;

        const actions = document.createElement('div');
        actions.className = 'combobox-actions';

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'combobox-clear-btn';
        clearBtn.innerHTML = '&times;';
        clearBtn.title = 'Hapus pilihan';

        const chevron = document.createElement('span');
        chevron.className = 'combobox-chevron';
        chevron.innerHTML = '&#9660;';

        actions.appendChild(clearBtn);
        actions.appendChild(chevron);

        control.appendChild(icon);
        control.appendChild(input);
        control.appendChild(actions);

        const dropdown = document.createElement('div');
        dropdown.className = 'combobox-dropdown';

        const countHeader = document.createElement('div');
        countHeader.className = 'combobox-count';

        const listUl = document.createElement('ul');
        listUl.className = 'combobox-list';

        dropdown.appendChild(countHeader);
        dropdown.appendChild(listUl);

        wrapper.appendChild(control);
        wrapper.appendChild(dropdown);

        selectEl.parentNode.insertBefore(wrapper, selectEl);
        selectEl.style.display = 'none';

        const getItems = () => {
            const items = [];
            Array.from(selectEl.options).forEach((opt, idx) => {
                if (!opt.value) return;
                items.push({
                    index: idx,
                    value: opt.value,
                    text: opt.text.trim(),
                });
            });
            return items;
        };

        let items = getItems();
        let filteredItems = [...items];
        let focusedIndex = -1;

        const syncFromSelect = () => {
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                input.value = selectedOpt.text.trim();
                clearBtn.style.display = 'block';
            } else {
                input.value = '';
                clearBtn.style.display = 'none';
            }
        };
        syncFromSelect();

        const renderList = (filterQuery = '') => {
            listUl.innerHTML = '';
            const q = filterQuery.toLowerCase().trim();

            filteredItems = items.filter(it => it.text.toLowerCase().includes(q));
            countHeader.textContent = `Ditemukan ${filteredItems.length} dari ${items.length} opsi`;

            if (filteredItems.length === 0) {
                const emptyLi = document.createElement('li');
                emptyLi.className = 'combobox-empty';
                emptyLi.textContent = 'Tidak ada pilihan yang cocok.';
                listUl.appendChild(emptyLi);
                return;
            }

            filteredItems.forEach((it, idx) => {
                const li = document.createElement('li');
                li.className = 'combobox-option' + (it.value === selectEl.value ? ' is-selected' : '');
                li.dataset.index = idx;

                if (q) {
                    const regex = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                    const parts = it.text.split(regex);
                    const span = document.createElement('span');
                    span.className = 'combobox-option-label';
                    parts.forEach(part => {
                        if (part.toLowerCase() === q) {
                            const h = document.createElement('mark');
                            h.className = 'combobox-highlight';
                            h.textContent = part;
                            span.appendChild(h);
                        } else {
                            span.appendChild(document.createTextNode(part));
                        }
                    });
                    li.appendChild(span);
                } else {
                    const span = document.createElement('span');
                    span.className = 'combobox-option-label';
                    span.textContent = it.text;
                    li.appendChild(span);
                }

                li.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectItem(it);
                });

                listUl.appendChild(li);
            });
        };

        const openDropdown = () => {
            renderList(input.value);
            wrapper.classList.add('is-open');
            focusedIndex = -1;
        };

        const closeDropdown = () => {
            wrapper.classList.remove('is-open');
            focusedIndex = -1;
            syncFromSelect();
        };

        const selectItem = (it) => {
            selectEl.value = it.value;
            input.value = it.text;
            clearBtn.style.display = 'block';
            closeDropdown();
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        };

        input.addEventListener('focus', () => {
            openDropdown();
        });

        input.addEventListener('input', () => {
            wrapper.classList.add('is-open');
            clearBtn.style.display = input.value ? 'block' : 'none';
            renderList(input.value);
        });

        clearBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            selectEl.value = '';
            input.value = '';
            clearBtn.style.display = 'none';
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
            renderList('');
            input.focus();
        });

        chevron.addEventListener('click', (e) => {
            e.stopPropagation();
            if (wrapper.classList.contains('is-open')) {
                closeDropdown();
            } else {
                openDropdown();
                input.focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (!wrapper.classList.contains('is-open')) {
                if (e.key === 'ArrowDown' || e.key === 'Enter') {
                    openDropdown();
                    e.preventDefault();
                }
                return;
            }

            const optionNodes = listUl.querySelectorAll('.combobox-option');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (focusedIndex < optionNodes.length - 1) focusedIndex++;
                updateFocus(optionNodes);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (focusedIndex > 0) focusedIndex--;
                updateFocus(optionNodes);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (focusedIndex >= 0 && optionNodes[focusedIndex]) {
                    const targetItem = filteredItems[focusedIndex];
                    if (targetItem) selectItem(targetItem);
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        function updateFocus(nodes) {
            nodes.forEach((n, idx) => {
                if (idx === focusedIndex) {
                    n.classList.add('is-focused');
                    n.scrollIntoView({ block: 'nearest' });
                } else {
                    n.classList.remove('is-focused');
                }
            });
        }

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                closeDropdown();
            }
        });
    };
});
