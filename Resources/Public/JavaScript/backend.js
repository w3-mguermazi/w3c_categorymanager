document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toggle-category').forEach(button => {
        button.addEventListener('click', () => {
            const uid = button.dataset.uid;
            const currentState = button.dataset.state; // "0" ou "1"

            fetch(TYPO3.settings.ajaxUrls['w3c-categorymanager_categorymodule_togglehide'], {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    uid: uid,
                    hidden: currentState === "1" ? 0 : 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.result.success) {
                    // Mise à jour de l'état
                    button.dataset.state = data.result.hidden;
                    
                    button.innerHTML = button.dataset.state == 1 
                        ? button.dataset.iconOff 
                        : button.dataset.iconOn;
                } else {
                    alert("Erreur lors de la mise à jour");
                }
            });
        });
    });
});
