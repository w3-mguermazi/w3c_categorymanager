document.addEventListener('DOMContentLoaded', () => {
    
    const wrapper = document.querySelector('.w3c_categorymanager');

    if (wrapper) {
        /**
         * Gestion du toggle hide (clic sur bouton œil)
         */
        wrapper.addEventListener('click', (e) => {
            const button = e.target.closest('.toggle-category');
            if (!button) return;

            const uid = button.dataset.uid;
            const currentState = button.dataset.state; // "0" ou "1"

            fetch(TYPO3.settings.ajaxUrls['w3c-categorymanager_categorymodule_togglehide'], {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ uid: uid, hidden: currentState === "1" ? 0 : 1 })
            })
            .then(response => response.json())
            .then(data => {
                if (data.result.success) {
                    // Mise à jour de l'état
                    button.dataset.state = data.result.hidden;
                    button.innerHTML = data.result.hidden == 1
                        ? document.getElementById('iconOff').innerHTML
                        : document.getElementById('iconOn').innerHTML;
                } else {
                    alert("Erreur lors de la mise à jour");
                }
            });
        });

        /**
         * Gestion du toggle expand/collapse (clic sur summary)
         */
    
        wrapper.querySelectorAll('details').forEach(detailsEl => {
            detailsEl.addEventListener('toggle', () => {
                const uid = detailsEl.dataset.uid;
                const state = detailsEl.open ? 1 : 0;

                fetch(TYPO3.settings.ajaxUrls['w3c-categorymanager_categorymodule_toggleexpand'], {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        uid: uid,
                        state: state
                    })
                });
            });
        });
    }
});
