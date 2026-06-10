import Alpine from 'alpinejs';
import Dexie from 'dexie';

const db = new Dexie('beerjudge');
db.version(1).stores({
    scoresheets: 'client_uuid, entry_id, syncStatus',
});

function bandInfo(total) {
    if (total >= 45) return { name: 'Outstanding', range: '45–50', description: 'World-class example of style.' };
    if (total >= 38) return { name: 'Excellent', range: '38–44', description: 'Exemplifies style well, requires minor fine-tuning.' };
    if (total >= 30) return { name: 'Very Good', range: '30–37', description: 'Generally within style parameters, some minor flaws.' };
    if (total >= 21) return { name: 'Good', range: '21–29', description: 'Misses the mark on style and/or minor flaws.' };
    if (total >= 14) return { name: 'Fair', range: '14–20', description: 'Off flavors/aromas or major style deficiencies. Unpleasant.' };
    return { name: 'Problematic', range: '0–13', description: 'Major off flavors and aromas dominate. Hard to drink.' };
}

function emptySheet(entryId) {
    return {
        client_uuid: crypto.randomUUID(),
        entry_id: entryId,
        aroma: 0,
        appearance: 0,
        flavour: 0,
        mouthfeel: 0,
        overall: 0,
        aroma_notes: '',
        appearance_notes: '',
        flavour_notes: '',
        mouthfeel_notes: '',
        overall_notes: '',
        status: 'draft',
        syncStatus: 'idle',
        expanded: false,
    };
}

window.judgingApp = function (serverData) {
    return {
        entries: serverData.entries,
        sheets: {},
        syncQueue: [],
        _retryTimer: null,
        styleGuide: { open: false, section: '', text: '' },

        async init() {
            // Seed from server data
            const serverByEntryId = {};
            for (const s of (serverData.existingSheets || [])) {
                serverByEntryId[s.entry_id] = s;
            }
            for (const entry of this.entries) {
                const server = serverByEntryId[entry.id];
                this.sheets[entry.id] = server
                    ? { ...server, syncStatus: 'synced', expanded: false }
                    : emptySheet(entry.id);
            }
            // Merge from IndexedDB — prefer IDB for pending/error items
            await this.mergeFromIndexedDB();
        },

        async mergeFromIndexedDB() {
            const entryIds = this.entries.map(e => e.id);
            const idbSheets = await db.scoresheets
                .where('entry_id').anyOf(entryIds).toArray();

            for (const idb of idbSheets) {
                if (['pending', 'error'].includes(idb.syncStatus)) {
                    const current = this.sheets[idb.entry_id];
                    this.sheets[idb.entry_id] = {
                        ...idb,
                        expanded: current?.expanded ?? false,
                    };
                    if (!this.syncQueue.includes(idb.entry_id)) {
                        this.syncQueue.push(idb.entry_id);
                    }
                }
            }
            if (this.syncQueue.length > 0) this.scheduleRetry();
        },

        sheetTotal(entryId) {
            const s = this.sheets[entryId];
            if (!s) return 0;
            return (s.aroma || 0) + (s.appearance || 0) + (s.flavour || 0)
                + (s.mouthfeel || 0) + (s.overall || 0);
        },

        bandInfo(total) {
            return bandInfo(total);
        },

        toggleExpanded(entryId) {
            this.sheets[entryId].expanded = !this.sheets[entryId].expanded;
        },

        async onInput(entryId) {
            await this.saveSheet(entryId);
        },

        async saveSheet(entryId) {
            const sheet = this.sheets[entryId];
            await db.scoresheets.put({ ...sheet, syncStatus: 'pending', updatedAt: new Date().toISOString() });
            this.sheets[entryId].syncStatus = 'pending';
            await this.syncToServer(entryId);
        },

        async syncToServer(entryId) {
            const sheet = this.sheets[entryId];
            this.sheets[entryId].syncStatus = 'syncing';
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch('/scoresheets', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        client_uuid: sheet.client_uuid,
                        entry_id: sheet.entry_id,
                        aroma: sheet.aroma,
                        appearance: sheet.appearance,
                        flavour: sheet.flavour,
                        mouthfeel: sheet.mouthfeel,
                        overall: sheet.overall,
                        aroma_notes: sheet.aroma_notes || null,
                        appearance_notes: sheet.appearance_notes || null,
                        flavour_notes: sheet.flavour_notes || null,
                        mouthfeel_notes: sheet.mouthfeel_notes || null,
                        overall_notes: sheet.overall_notes || null,
                        status: sheet.status,
                    }),
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const json = await res.json();
                this.sheets[entryId].syncStatus = 'synced';
                this.sheets[entryId].status = json.status;
                await db.scoresheets.put({ ...this.sheets[entryId], syncStatus: 'synced' });
            } catch {
                this.sheets[entryId].syncStatus = 'error';
                await db.scoresheets.put({ ...this.sheets[entryId], syncStatus: 'error' });
                if (!this.syncQueue.includes(entryId)) this.syncQueue.push(entryId);
                this.scheduleRetry();
            }
        },

        scheduleRetry() {
            if (this._retryTimer) return;
            this._retryTimer = setTimeout(async () => {
                this._retryTimer = null;
                const queue = [...this.syncQueue];
                this.syncQueue = [];
                for (const entryId of queue) {
                    await this.syncToServer(entryId);
                }
            }, 5000);
        },

        async submitSheet(entryId) {
            this.sheets[entryId].status = 'submitted';
            await this.saveSheet(entryId);
        },

        async reopenSheet(entryId) {
            this.sheets[entryId].status = 'draft';
            await this.saveSheet(entryId);
        },

        retryNow(entryId) {
            if (!this.syncQueue.includes(entryId)) this.syncQueue.push(entryId);
            if (this._retryTimer) clearTimeout(this._retryTimer);
            this._retryTimer = null;
            this.scheduleRetry();
        },

        openGuide(entryId, sectionKey) {
            const entry = this.entries.find(e => e.id === entryId);
            const titles = {
                aroma: 'Aroma', appearance: 'Appearance', flavor: 'Flavour',
                mouthfeel: 'Mouthfeel', overall_impression: 'Overall Impression',
            };
            this.styleGuide = {
                open: true,
                section: titles[sectionKey] ?? sectionKey,
                text: entry?.style_guide?.[sectionKey] ?? 'No descriptor available.',
            };
        },

        closeGuide() {
            this.styleGuide.open = false;
        },
    };
};

Alpine.start();
