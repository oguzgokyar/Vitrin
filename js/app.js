document.addEventListener('alpine:init', () => {
    Alpine.store('apps', {
        items: [],
        async init() {
            try {
                // Fetch data from server on init
                const response = await fetch('data.json?t=' + Date.now());
                if (response.ok) {
                    this.items = await response.json();
                }
            } catch (e) {
                console.error('Failed to load data', e);
                this.items = [];
            }
        },
        add(app) {
            this.items.push({
                id: Date.now(),
                rating: 0,
                votes: 0,
                ...app,
                createdAt: new Date().toISOString()
            });
            this.save();
        },
        remove(id) {
            this.items = this.items.filter(item => item.id !== id);
            this.save();
        },
        update(updatedApp) {
            const index = this.items.findIndex(item => item.id === updatedApp.id);
            if (index !== -1) {
                this.items[index] = { ...this.items[index], ...updatedApp };
                this.save();
            }
        },
        async rate(id, score) {
            // Optimistic update for UI
            const item = this.items.find(i => i.id === id);
            if (item) {
                const totalScore = (item.rating * item.votes) + score;
                item.votes += 1;
                item.rating = totalScore / item.votes;
            }

            // Send vote to server
            try {
                await fetch('save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'vote', id, score })
                });
            } catch (e) {
                console.error('Vote failed', e);
            }
        },
        async save() {
            // Send all data to server (Admin only)
            // We get the password strictly from the prompt or assume admin123 for now since it's hardcoded in PHP
            // Ideally auth should be better, but for this simple task:
            try {
                await fetch('save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_all',
                        data: this.items,
                        password: 'admin123' // Sending hardcoded password as agreed
                    })
                });
            } catch (e) {
                console.error('Save failed', e);
                alert('Kaydetme başarısız! Sunucu ayarlarını kontrol edin.');
            }
        }
    });
});
