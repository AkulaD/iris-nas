document.addEventListener("DOMContentLoaded", function () {
    const loadingScreen = document.getElementById("loading-screen");
    if (loadingScreen) {
        const removeLoadingScreen = () => {
            loadingScreen.classList.add("fade-out");
            setTimeout(() => loadingScreen.remove(), 500);
        };
        if (document.readyState === "complete") {
            removeLoadingScreen();
        } else {
            window.addEventListener("load", removeLoadingScreen);
            setTimeout(removeLoadingScreen, 3000);
        }
    }

    const driveItems = Array.from(document.querySelectorAll('.drive-item'));
    const contextMenu = document.getElementById('custom-context-menu');
    const workspace = document.getElementById('drive-workspace');
    
    let lastSelectedIndex = -1;
    let clipboard = { action: null, items: [] };

    const savedClipboard = sessionStorage.getItem('clipboard');
    if (savedClipboard) {
        clipboard = JSON.parse(savedClipboard);
        if (clipboard.action === 'cut') {
            clipboard.items.forEach(id => {
                const el = document.querySelector(`[data-id="${id}"]`);
                if (el) el.style.opacity = '0.5';
            });
        }
    }

    const selectionBox = document.createElement('div');
    selectionBox.style.position = 'absolute';
    selectionBox.style.border = '1px solid rgba(13, 110, 253, 0.5)';
    selectionBox.style.backgroundColor = 'rgba(13, 110, 253, 0.1)';
    selectionBox.style.display = 'none';
    selectionBox.style.pointerEvents = 'none';
    selectionBox.style.zIndex = '9999';
    document.body.appendChild(selectionBox);

    let isDragging = false;
    let startX, startY;

    function clearSelection() {
        driveItems.forEach(item => {
            item.classList.remove('item-selected');
            item.style.outline = 'none';
            item.style.backgroundColor = '';
        });
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.item-selected')).map(el => el.dataset.id);
    }

    function styleSelected(item) {
        item.classList.add('item-selected');
        item.style.outline = '2px solid #0d6efd';
        item.style.backgroundColor = '#f1f8ff';
    }

    if (workspace) {
        workspace.addEventListener('mousedown', (e) => {
            if (e.target.closest('.drive-item') || e.button !== 0) return;
            isDragging = true;
            startX = e.pageX;
            startY = e.pageY;
            selectionBox.style.left = startX + 'px';
            selectionBox.style.top = startY + 'px';
            selectionBox.style.width = '0px';
            selectionBox.style.height = '0px';
            selectionBox.style.display = 'block';

            if (!e.ctrlKey && !e.shiftKey) {
                clearSelection();
                lastSelectedIndex = -1;
            }
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            const currentX = e.pageX;
            const currentY = e.pageY;
            
            const width = Math.abs(currentX - startX);
            const height = Math.abs(currentY - startY);
            
            selectionBox.style.width = width + 'px';
            selectionBox.style.height = height + 'px';
            selectionBox.style.left = Math.min(startX, currentX) + 'px';
            selectionBox.style.top = Math.min(startY, currentY) + 'px';

            const boxRect = selectionBox.getBoundingClientRect();
            
            driveItems.forEach((item, index) => {
                const itemRect = item.getBoundingClientRect();
                const isOverlapping = !(boxRect.right < itemRect.left || 
                                        boxRect.left > itemRect.right || 
                                        boxRect.bottom < itemRect.top || 
                                        boxRect.top > itemRect.bottom);
                
                if (isOverlapping) {
                    styleSelected(item);
                    lastSelectedIndex = index;
                } else if (!e.ctrlKey && !e.shiftKey) {
                    item.classList.remove('item-selected');
                    item.style.outline = 'none';
                    item.style.backgroundColor = '';
                }
            });
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
            selectionBox.style.display = 'none';
        });
    }

    driveItems.forEach((item, index) => {
        const menuBtn = item.querySelector('.action-menu-btn');
        if (menuBtn) {
            menuBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                clearSelection();
                styleSelected(item);
                lastSelectedIndex = index;

                if (!contextMenu) return;
                contextMenu.style.display = 'block';
                
                const rect = menuBtn.getBoundingClientRect();
                let x = rect.left;
                let y = rect.bottom;
                
                if (x + contextMenu.offsetWidth > window.innerWidth) {
                    x = window.innerWidth - contextMenu.offsetWidth - 10;
                }
                if (y + contextMenu.offsetHeight > window.innerHeight) {
                    y = rect.top - contextMenu.offsetHeight - 5;
                }
                
                contextMenu.style.left = `${x}px`;
                contextMenu.style.top = `${y}px`;
            });
        }

        item.addEventListener('click', (e) => {
            if (e.target.closest('.action-menu-btn')) return;

            e.stopPropagation(); 
            
            if (e.shiftKey && lastSelectedIndex !== -1) {
                document.getSelection().removeAllRanges(); 
                let start = Math.min(lastSelectedIndex, index);
                let end = Math.max(lastSelectedIndex, index);
                
                if (!e.ctrlKey) clearSelection(); 
                
                for (let i = start; i <= end; i++) {
                    styleSelected(driveItems[i]);
                }
            } else if (e.ctrlKey || e.metaKey) {
                if (item.classList.contains('item-selected')) {
                    item.classList.remove('item-selected');
                    item.style.outline = 'none';
                    item.style.backgroundColor = '';
                } else {
                    styleSelected(item);
                }
            } else {
                clearSelection();
                styleSelected(item);
            }
            lastSelectedIndex = index;
        });

        item.addEventListener('dblclick', (e) => {
            if(item.dataset.type === 'file' && item.dataset.url) {
                window.open(item.dataset.url, '_blank');
            } else if (item.dataset.type === 'folder' && item.dataset.url) {
                window.location.href = item.dataset.url;
            }
        });

        item.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!item.classList.contains('item-selected')) {
                clearSelection();
                styleSelected(item);
                lastSelectedIndex = index;
            }
            
            if (!contextMenu) return;
            contextMenu.style.display = 'block';
            let x = e.clientX;
            let y = e.clientY;
            
            if (x + contextMenu.offsetWidth > window.innerWidth) x -= contextMenu.offsetWidth;
            if (y + contextMenu.offsetHeight > window.innerHeight) y -= contextMenu.offsetHeight;
            
            contextMenu.style.left = `${x}px`;
            contextMenu.style.top = `${y}px`;
        });
    });

    document.addEventListener('click', () => {
        if (contextMenu) contextMenu.style.display = 'none';
    });

    document.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

        const isCtrl = e.ctrlKey || e.metaKey;

        if (isCtrl && e.key.toLowerCase() === 'a') {
            e.preventDefault();
            driveItems.forEach(item => styleSelected(item));
        } 
        else if (isCtrl && e.key.toLowerCase() === 'c') {
            clipboard.action = 'copy';
            clipboard.items = getSelectedIds();
            sessionStorage.setItem('clipboard', JSON.stringify(clipboard));
            driveItems.forEach(el => el.style.opacity = '1');
        } 
        else if (isCtrl && e.key.toLowerCase() === 'x') {
            clipboard.action = 'cut';
            clipboard.items = getSelectedIds();
            sessionStorage.setItem('clipboard', JSON.stringify(clipboard));
            document.querySelectorAll('.item-selected').forEach(el => el.style.opacity = '0.5'); 
        } 
        else if (isCtrl && e.key.toLowerCase() === 'v') {
            if(clipboard.items.length > 0) {
                const currentDir = new URLSearchParams(window.location.search).get('dir') || 'root';
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'tasks/paste_action.php';

                const actInput = document.createElement('input');
                actInput.type = 'hidden';
                actInput.name = 'action';
                actInput.value = clipboard.action;
                
                const dirInput = document.createElement('input');
                dirInput.type = 'hidden';
                dirInput.name = 'target_dir';
                dirInput.value = currentDir;

                const itemsInput = document.createElement('input');
                itemsInput.type = 'hidden';
                itemsInput.name = 'items';
                itemsInput.value = JSON.stringify(clipboard.items);

                form.appendChild(actInput);
                form.appendChild(dirInput);
                form.appendChild(itemsInput);
                document.body.appendChild(form);
                
                sessionStorage.removeItem('clipboard');
                form.submit();
            }
        }
        else if (e.key === 'Delete') {
            const toDelete = getSelectedIds();
            if (toDelete.length > 0) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'tasks/delete_action.php';
                
                const itemsInput = document.createElement('input');
                itemsInput.type = 'hidden';
                itemsInput.name = 'items';
                itemsInput.value = JSON.stringify(toDelete);
                
                form.appendChild(itemsInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    });

    if (contextMenu) {
        contextMenu.addEventListener('click', (e) => {
            const actionTarget = e.target.closest('a[data-action]');
            if (!actionTarget) return;

            e.preventDefault();
            const action = actionTarget.dataset.action;
            const selectedIds = getSelectedIds();

            if (action === 'copy') {
                document.dispatchEvent(new KeyboardEvent('keydown', {key: 'c', ctrlKey: true}));
            } else if (action === 'cut') {
                document.dispatchEvent(new KeyboardEvent('keydown', {key: 'x', ctrlKey: true}));
            } else if (action === 'paste') {
                document.dispatchEvent(new KeyboardEvent('keydown', {key: 'v', ctrlKey: true}));
            } else if (action === 'delete') {
                document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Delete'}));
            } else if (action === 'rename') {
                if (selectedIds.length === 1) {
                    const renameModalElement = document.getElementById('renameModal');
                    const renameModal = new bootstrap.Modal(renameModalElement);
                    const selectedEl = document.querySelector(`[data-id="${selectedIds[0]}"]`);
                    
                    document.getElementById('renameItemId').value = selectedIds[0];
                    document.getElementById('renameItemName').value = selectedEl.dataset.name;
                    
                    renameModal.show();
                    
                    renameModalElement.addEventListener('shown.bs.modal', function () {
                        const inputField = document.getElementById('renameItemName');
                        inputField.focus();
                        const lastDot = inputField.value.lastIndexOf('.');
                        if(lastDot > 0) {
                            inputField.setSelectionRange(0, lastDot);
                        } else {
                            inputField.select();
                        }
                    }, { once: true });
                } else {
                    alert("Pilih tepat satu item untuk mengganti nama.");
                }
            } else if (action === 'star') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'tasks/star_action.php';
                const itemsInput = document.createElement('input');
                itemsInput.type = 'hidden';
                itemsInput.name = 'items';
                itemsInput.value = JSON.stringify(selectedIds);
                form.appendChild(itemsInput);
                document.body.appendChild(form);
                form.submit();
            } else if (action === 'download') {
                if (selectedIds.length > 0) {
                    const downloadToast = document.getElementById('download-toast');
                    
                    if (downloadToast) downloadToast.style.display = 'block';

                    const token = 'dl_' + Date.now().toString();

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'tasks/download_action.php';

                    const itemsInput = document.createElement('input');
                    itemsInput.type = 'hidden';
                    itemsInput.name = 'items';
                    itemsInput.value = JSON.stringify(selectedIds);

                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = 'download_token';
                    tokenInput.value = token;

                    form.appendChild(itemsInput);
                    form.appendChild(tokenInput);
                    document.body.appendChild(form);
                    
                    form.submit();
                    document.body.removeChild(form);

                    const pollTimer = setInterval(() => {
                        if (document.cookie.indexOf("download_token=" + token) !== -1) {
                            clearInterval(pollTimer);
                            if (downloadToast) downloadToast.style.display = 'none';
                            
                            document.cookie = "download_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                        }
                    }, 500);
                } else {
                    alert("Pilih file yang ingin didownload.");
                }
            } else if (action === 'details') {
                if (selectedIds.length === 1) {
                    const detailsModalElement = document.getElementById('detailsModal');
                    const detailsModal = new bootstrap.Modal(detailsModalElement);
                    const selectedEl = document.querySelector(`[data-id="${selectedIds[0]}"]`);

                    document.getElementById('detail-name').textContent = selectedEl.dataset.name;
                    document.getElementById('detail-type').textContent = selectedEl.dataset.type;
                    document.getElementById('detail-size').textContent = selectedEl.dataset.size;
                    document.getElementById('detail-date').textContent = selectedEl.dataset.date;

                    const shareToken = selectedEl.dataset.shareToken;
                    const shareArea = document.getElementById('detail-share-area');
                    const shareLinkEl = document.getElementById('detail-share-link');

                    if (shareToken && shareToken.trim() !== '') {
                        const fullLink = window.location.origin + '/iris-nas/view.php?id=' + shareToken;
                        shareLinkEl.href = fullLink;
                        shareLinkEl.textContent = fullLink;
                        if (shareArea) shareArea.style.display = 'block';
                    } else {
                        if (shareArea) shareArea.style.display = 'none';
                    }

                    detailsModal.show();
                } else {
                    alert("Pilih tepat satu item untuk melihat detail.");
                }
            }
        });
    }

    const btnBulkRecover = document.getElementById('btn-bulk-recover');
    const btnBulkDeletePerm = document.getElementById('btn-bulk-delete-perm');

    function updateTrashBulkButtons() {
        const selectedIds = getSelectedIds();
        if (selectedIds.length > 0) {
            if (btnBulkRecover) btnBulkRecover.classList.remove('d-none');
            if (btnBulkDeletePerm) btnBulkDeletePerm.classList.remove('d-none');
        } else {
            if (btnBulkRecover) btnBulkRecover.classList.add('d-none');
            if (btnBulkDeletePerm) btnBulkDeletePerm.classList.add('d-none');
        }
    }

    if (workspace) {
        workspace.addEventListener('mouseup', () => setTimeout(updateTrashBulkButtons, 50));
        workspace.addEventListener('click', () => setTimeout(updateTrashBulkButtons, 50));
    }

    if (contextMenu) {
        contextMenu.addEventListener('click', (e) => {
            const actionTarget = e.target.closest('a[data-action]');
            if (!actionTarget) return;

            const action = actionTarget.dataset.action;
            const selectedIds = getSelectedIds();

            if (action === 'recover') {
                executeRecover(selectedIds);
            } else if (action === 'delete_permanent') {
                executePermanentDelete(selectedIds);
            }
        });
    }

    if (btnBulkRecover) {
        btnBulkRecover.addEventListener('mousedown', (e) => {
            e.stopPropagation();
        });
        btnBulkRecover.addEventListener('click', (e) => {
            e.stopPropagation();
            executeRecover(getSelectedIds());
        });
    }

    if (btnBulkDeletePerm) {
        btnBulkDeletePerm.addEventListener('mousedown', (e) => {
            e.stopPropagation();
        });
        btnBulkDeletePerm.addEventListener('click', (e) => {
            e.stopPropagation();
            executePermanentDelete(getSelectedIds());
        });
    }

    function executeRecover(ids) {
        if (ids.length === 0) return;
        if (confirm(`Are you sure you want to restore ${ids.length} selected item(s)?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'tasks/recover_action.php';

            const itemsInput = document.createElement('input');
            itemsInput.type = 'hidden';
            itemsInput.name = 'items';
            itemsInput.value = JSON.stringify(ids);

            form.appendChild(itemsInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function executePermanentDelete(ids) {
        if (ids.length === 0) return;
        if (confirm(`WARNING: Are you sure you want to permanently delete ${ids.length} selected item(s)? The files will be completely removed from the storage disk and this action cannot be undone.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'tasks/delete_permanent_action.php';

            const itemsInput = document.createElement('input');
            itemsInput.type = 'hidden';
            itemsInput.name = 'items';
            itemsInput.value = JSON.stringify(ids);

            form.appendChild(itemsInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    const btnCopyShareLink = document.getElementById('btnCopyShareLink');
    if (btnCopyShareLink) {
        btnCopyShareLink.addEventListener('click', function() {
            const shareLinkInput = document.getElementById('shareLinkInput');
            if (shareLinkInput && shareLinkInput.value.trim() !== '') {
                navigator.clipboard.writeText(shareLinkInput.value)
                .then(() => {
                    const originalHTML = btnCopyShareLink.innerHTML;
                    btnCopyShareLink.innerHTML = '<i class="bi bi-check2 me-1"></i> Tersalin!';
                    btnCopyShareLink.classList.replace('btn-primary', 'btn-success');
                    
                    setTimeout(() => {
                        btnCopyShareLink.innerHTML = originalHTML;
                        btnCopyShareLink.classList.replace('btn-success', 'btn-primary');
                    }, 2000);
                })
                .catch(err => {
                    console.error('Gagal menyalin tautan: ', err);
                    alert('Gagal menyalin link.');
                });
            }
        });
    }
});

document.querySelectorAll('.drive-item').forEach(item => {
    item.addEventListener('contextmenu', e => {
        const isStarred = item.getAttribute('data-starred');
        
        const textStar = document.getElementById('text-star-action');
        const iconStar = document.getElementById('icon-star-action');
        
        if (isStarred === '1') {
            if (textStar) textStar.textContent = 'Hapus Bintang';
            if (iconStar) iconStar.className = 'bi bi-star-fill text-warning';
        } else {
            if (textStar) textStar.textContent = 'Beri Bintang';
            if (iconStar) iconStar.className = 'bi bi-star text-muted';
        }
    });
});

document.querySelectorAll('.drive-item').forEach(item => {
    item.addEventListener('contextmenu', e => {
        const shareToken = item.getAttribute('data-share-token');
        
        const textShare = document.getElementById('text-share-action');
        const iconShare = document.getElementById('icon-share-action');
        
        if (shareToken && shareToken.trim() !== '') {
            if (textShare) textShare.textContent = 'Hapus Bagikan';
            if (iconShare) iconShare.className = 'bi bi-share-fill text-primary';
        } else {
            if (textShare) textShare.textContent = 'Bagikan';
            if (iconShare) iconShare.className = 'bi bi-share text-muted';
        }
    });
});

document.addEventListener("DOMContentLoaded", function() {
    const contextMenu = document.getElementById('custom-context-menu');
    
    if (contextMenu) {
        contextMenu.addEventListener('click', function(e) {
            const actionTarget = e.target.closest('a[data-action]');
            if (!actionTarget) return;

            const action = actionTarget.dataset.action;
            if (action === 'share') {
                e.preventDefault();
                const selectedEl = document.querySelector('.item-selected');
                if (!selectedEl) {
                    alert("Pilih file atau folder terlebih dahulu.");
                    return;
                }

                const itemId = selectedEl.dataset.id;
                const itemName = selectedEl.dataset.name;
                const currentToken = selectedEl.dataset.shareToken;
                const isShared = currentToken && currentToken.trim() !== '';
                const requestAction = isShared ? 'unshare' : 'share';

                if (requestAction === 'unshare') {
                    if (!confirm("Apakah Anda yakin ingin menghapus akses berbagi untuk file ini?")) {
                        return;
                    }
                }
                
                fetch('tasks/share_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(itemId) + '&action=' + encodeURIComponent(requestAction)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (requestAction === 'unshare') {
                            selectedEl.dataset.shareToken = '';
                            location.reload();
                        } else {
                            const token = data.share_token;
                            const fullLink = window.location.origin + '/iris-nas/view.php?id=' + token;

                            selectedEl.dataset.shareToken = token;
                            selectedEl.dataset.shareLink = fullLink;

                            document.getElementById('share-item-name').textContent = itemName;
                            document.getElementById('shareLinkInput').value = fullLink;
                            
                            const alertBox = document.getElementById('share-success-alert');
                            if (alertBox) alertBox.style.display = 'block';

                            const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
                            shareModal.show();
                        }
                    } else {
                        alert("Gagal memproses permintaan: " + (data.message || 'Terjadi kesalahan'));
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Terjadi kesalahan koneksi server.");
                });
            }
        });
    }
});