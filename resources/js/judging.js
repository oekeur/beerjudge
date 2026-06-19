import './sentry.js';
import Alpine from 'alpinejs';
import Dexie from 'dexie';

const db = new Dexie('beerjudge');
db.version(1).stores({
    scoresheets: 'client_uuid, entry_id, syncStatus',
});


const SRM_HEX = [
    '#FFE699', '#FFD878', '#FFCA5A', '#FFBF42', '#FBB123',
    '#F8A600', '#F39C00', '#EA8F00', '#E58500', '#DE7C00',
    '#D77200', '#CF6900', '#CB6200', '#C35900', '#BB5100',
    '#B54C00', '#B04500', '#A63E00', '#A13700', '#9B3200',
    '#952D00', '#8E2900', '#882300', '#821E00', '#7B1A00',
    '#771900', '#701400', '#6A0E00', '#660D00', '#5E0B00',
    '#5A0A00', '#560A00', '#520900', '#4C0700', '#470600',
    '#440600', '#3F0500', '#3B0400', '#360400', '#330200',
];

window.srmToHex = function srmToHex(srm) {
    const index = Math.max(0, Math.min(39, Math.round(srm) - 1));
    return SRM_HEX[index];
};

function emptySheet(entryId) {
    return {
        client_uuid: crypto.randomUUID(),
        entry_id: entryId,
        aroma: null,
        appearance: null,
        flavour: null,
        mouthfeel: null,
        overall: null,
        aroma_notes: '',
        appearance_notes: '',
        flavour_notes: '',
        mouthfeel_notes: '',
        overall_notes: '',
        descriptors: [],
        status: 'draft',
        syncStatus: 'idle',
        expanded: false,
        submitAttempted: false,
    };
}

window.judgingApp = function (serverData) {
    return {
        entries: serverData.entries,
        bands: serverData.bands,
        sheets: {},
        sortMode: 'judging',
        syncQueue: [],
        _retryTimer: null,
        styleGuide: { open: false, section: '', text: '', srmMin: null, srmMax: null },
        styleInfo: { open: false, styleName: '', styleCode: '', categoryName: '', categoryDescription: '', history: '', styleComparison: '' },

        get sortedEntries() {
            if (this.sortMode === 'judging') return this.entries;
            return [...this.entries].sort((a, b) => {
                const ta = this.sheetTotal(a.id);
                const tb = this.sheetTotal(b.id);
                if (ta !== tb) return tb - ta;
                const oa = this.sheets[a.id]?.overall ?? 0;
                const ob = this.sheets[b.id]?.overall ?? 0;
                return ob - oa;
            });
        },

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
            let idbSheets = [];
            try {
                idbSheets = await db.scoresheets
                    .where('entry_id').anyOf(this.entries.map(e => e.id)).toArray();
            } catch (e) {
                console.warn('IDB read failed:', e);
                return;
            }

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
            return this.bands.find(b => total >= b.min) ?? this.bands[this.bands.length - 1];
        },

        scoreColor(total) {
            const hue = Math.round((total / 50) * 120);
            return `hsl(${hue}, 78%, var(--score-lum))`;
        },

        toggleExpanded(entryId) {
            this.sheets[entryId].expanded = !this.sheets[entryId].expanded;
        },

        async onInput(entryId) {
            await this.saveSheet(entryId);
        },

        async saveSheet(entryId) {
            const sheet = this.sheets[entryId];
            try {
                await db.scoresheets.put({ ...JSON.parse(JSON.stringify(sheet)), syncStatus: 'pending', updatedAt: new Date().toISOString() });
            } catch (e) {
                console.warn('IDB write failed (pending):', e);
            }
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
                        descriptors: sheet.descriptors || [],
                        status: sheet.status,
                    }),
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const json = await res.json();
                this.sheets[entryId].syncStatus = 'synced';
                // Don't let a stale draft-save response downgrade a submitted sheet
                if (json.status === 'submitted' || this.sheets[entryId].status !== 'submitted') {
                    this.sheets[entryId].status = json.status;
                }
                try {
                    await db.scoresheets.put({ ...JSON.parse(JSON.stringify(this.sheets[entryId])), syncStatus: 'synced' });
                } catch (e) {
                    console.warn('IDB write failed (synced):', e);
                }
            } catch (err) {
                console.error('syncToServer failed', entryId, String(err));
                this.sheets[entryId].syncStatus = 'error';
                try {
                    await db.scoresheets.put({ ...JSON.parse(JSON.stringify(this.sheets[entryId])), syncStatus: 'error' });
                } catch (e) {
                    console.warn('IDB write failed (error):', e);
                }
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

        isScoreComplete(entryId) {
            const s = this.sheets[entryId];
            return s && s.aroma !== null && s.appearance !== null
                && s.flavour !== null && s.mouthfeel !== null && s.overall !== null;
        },

        async submitSheet(entryId) {
            if (!this.isScoreComplete(entryId)) {
                this.sheets[entryId].submitAttempted = true;
                return;
            }
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
                srmMin: sectionKey === 'appearance' ? (entry?.srm_min ?? null) : null,
                srmMax: sectionKey === 'appearance' ? (entry?.srm_max ?? null) : null,
            };
        },

        closeGuide() {
            this.styleGuide.open = false;
        },

        openStyleInfo(entryId) {
            const entry = this.entries.find(e => e.id === entryId);
            this.styleInfo = {
                open: true,
                styleName: entry?.style_name ?? '',
                styleCode: entry?.style_code ?? '',
                categoryName: entry?.style_info?.category_name ?? '',
                categoryDescription: entry?.style_info?.category_description ?? '',
                history: entry?.style_info?.history ?? '',
                styleComparison: entry?.style_info?.style_comparison ?? '',
            };
        },

        srmHasBoth() {
            return this.styleGuide.srmMin !== null && this.styleGuide.srmMax !== null;
        },
        srmSingleVal() {
            return this.styleGuide.srmMin !== null ? this.styleGuide.srmMin : this.styleGuide.srmMax;
        },
        srmPadding() {
            if (!this.srmHasBoth()) return 3;
            return Math.max(3, Math.round((this.styleGuide.srmMax - this.styleGuide.srmMin) * 0.3));
        },
        srmDisplayMin() {
            return Math.max(1, (this.styleGuide.srmMin ?? this.srmSingleVal()) - this.srmPadding());
        },
        srmDisplayMax() {
            return Math.min(40, (this.styleGuide.srmMax ?? this.srmSingleVal()) + this.srmPadding());
        },
        srmRange() {
            return this.srmDisplayMax() - this.srmDisplayMin();
        },
        srmLeftPct(srm) {
            if (srm === null) return '50%';
            return ((srm - this.srmDisplayMin()) / this.srmRange() * 100).toFixed(2) + '%';
        },
        srmLeftMaskPct() {
            const min = this.styleGuide.srmMin ?? this.srmSingleVal();
            return ((min - this.srmDisplayMin()) / this.srmRange() * 100).toFixed(2) + '%';
        },
        srmRightMaskPct() {
            const max = this.styleGuide.srmMax ?? this.srmSingleVal();
            return ((this.srmDisplayMax() - max) / this.srmRange() * 100).toFixed(2) + '%';
        },
        srmGradientStyle() {
            if (!this.srmHasBoth()) {
                return 'top:4px;height:24px;background:' + srmToHex(this.srmSingleVal());
            }
            return 'top:4px;height:24px;background:linear-gradient(to right,' + srmToHex(this.srmDisplayMin()) + ',' + srmToHex(this.srmDisplayMax()) + ')';
        },

        closeStyleInfo() {
            this.styleInfo.open = false;
        },

        async toggleDescriptor(entryId, slug) {
            const sheet = this.sheets[entryId];
            const idx = (sheet.descriptors || []).indexOf(slug);
            if (idx === -1) {
                sheet.descriptors = [...(sheet.descriptors || []), slug];
            } else {
                sheet.descriptors = sheet.descriptors.filter(s => s !== slug);
            }
            await this.saveSheet(entryId);
        },
    };
};

Alpine.start();
