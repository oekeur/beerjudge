import './sentry.js';
import Alpine from 'alpinejs';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {})
    })
}
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

// The part of a sheet that is saved to the server. A save captures it together with its revision,
// so a request or retry always sends the content that revision stands for, never the live sheet.
const CONTENT_FIELDS = [
    'aroma', 'appearance', 'flavour', 'mouthfeel', 'overall',
    'aroma_notes', 'appearance_notes', 'flavour_notes', 'mouthfeel_notes', 'overall_notes',
    'descriptors', 'status',
];

function contentOf(sheet) {
    return JSON.parse(JSON.stringify(Object.fromEntries(CONTENT_FIELDS.map(field => [field, sheet[field] ?? null]))));
}

// The characters Laravel's TrimStrings removes (Str::trim: \s plus Str::INVISIBLE_CHARACTERS).
const TRIM_CLASS = '[\\s\\u0009\\u0020\\u00A0\\u00AD\\u034F\\u061C\\u115F\\u1160\\u17B4\\u17B5\\u180E\\u2000-\\u200F\\u202F\\u205F'
    + '\\u2060-\\u2065\\u206A-\\u206F\\u3000\\u2800\\u3164\\uFEFF\\uFFA0\\u{1D159}\\u{1D173}-\\u{1D17A}\\u{E0020}\\0]';
const TRIM_PATTERN = new RegExp(`^${TRIM_CLASS}+|${TRIM_CLASS}+$`, 'gu');
const serverTrim = (value) => String(value ?? '').replace(TRIM_PATTERN, '');

// Compares like the server stores: strings trimmed, null/''/whitespace equal, descriptors unordered.
function sameContent(a, b) {
    const normalise = (content) => Object.fromEntries(CONTENT_FIELDS.map((field) => {
        const value = content[field] ?? null;
        if (field.endsWith('_notes')) return [field, serverTrim(value)];
        if (field === 'descriptors') return [field, [...(value ?? [])].sort()];
        return [field, value];
    }));
    return JSON.stringify(normalise(a)) === JSON.stringify(normalise(b));
}

function withSaved(sheet, revision) {
    return { ...sheet, revision, saved: { revision, content: contentOf(sheet) } };
}

function emptySheet(entryId) {
    return withSaved({
        client_uuid: crypto.randomUUID(),
        revision: 0,
        notice: false,
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
    }, 0);
}

window.judgingApp = function (serverData, labels = { sections: {}, guide: {}, styleInfo: '' }) {
    return {
        entries: serverData.entries,
        bands: serverData.bands,
        sheets: {},
        sortMode: 'judging',
        syncQueue: [],
        _retryTimer: null,
        // Only the tab that holds the Web Lock for this competition syncs and writes IndexedDB.
        // The others show a banner; see tryLock().
        hasLock: false,
        inactive: false,
        ready: false,
        // Bumped on every activation and deactivation. Async work captures it and bails when it changed.
        _epoch: 0,
        _onlineHandler: null,
        _progressTimer: null,
        // Per-activation save bookkeeping (chains of in-flight saves, saves waiting per entry).
        _sync: { chains: {}, waiting: {} },
        _saveVersion: {},
        styleGuide: { open: false, section: '', text: '', srm: null },
        styleInfo: { open: false, styleName: '', styleCode: '', categoryName: '', categoryDescription: '', history: '', styleComparison: '', attribution: null },
        progress: {},
        progressOpen: false,

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
            for (const entry of this.entries) {
                this.sheets[entry.id] = this.seedSheet(entry);
            }

            // Without the Web Locks API (very old browsers) the single-tab assumption stands.
            if (!navigator.locks) {
                await this.activate();
            } else if (await this.tryLock(false)) {
                await this.activate();
            } else {
                this.inactive = true;
            }
            this.ready = true;
        },

        // Two tabs of one browser share IndexedDB but not memory, and their writes overwrite each
        // other's rows. One tab per competition is active: it holds this lock for its lifetime
        // (the promise never settles, so closing or crashing the tab releases it).
        tryLock(steal) {
            return new Promise((resolve) => {
                const options = steal ? { steal: true } : { ifAvailable: true };
                navigator.locks.request(`beerjudge-judging-${serverData.competitionId}`, options, async (lock) => {
                    if (!lock) {
                        resolve(false);
                        return;
                    }
                    resolve(true);
                    await new Promise(() => {});
                }).catch((err) => {
                    if (err?.name === 'AbortError') {
                        // Another tab took the lock over: stop everything at once.
                        this.deactivate();
                    } else {
                        console.error('Web Lock request failed', err);
                    }
                    resolve(false);
                });
            });
        },

        async takeOver() {
            if (await this.tryLock(true)) {
                await this.activate();
            } else {
                console.error('Could not take over the judging lock; this tab stays inactive.');
            }
        },

        isCurrent(epoch) {
            return this.hasLock && epoch === this._epoch;
        },

        // Starts an active period. The tab's sync state is rebuilt from IndexedDB, which is
        // authoritative after a take-over, not merged onto whatever this tab kept in memory from an
        // earlier period. Every step after an await checks that the period is still current.
        async activate() {
            const epoch = ++this._epoch;
            this.hasLock = true;
            this.inactive = false;
            this._sync = { chains: {}, waiting: {} };
            this.syncQueue = [];
            if (this._retryTimer) clearTimeout(this._retryTimer);
            this._retryTimer = null;
            await this.rebuildFromIndexedDB(epoch);
            if (!this.isCurrent(epoch)) return;
            this._onlineHandler = () => this.flushQueue();
            window.addEventListener('online', this._onlineHandler);
            if (serverData.isAdmin) {
                this.fetchProgress();
                this._progressTimer = setInterval(() => this.fetchProgress(), 30000);
            }
            if (this.syncQueue.length > 0) this.flushQueue();
        },

        // Lost the lock: no timers, no listener, no queue, and every IndexedDB write and response
        // handler checks its epoch first, so work still in flight cannot touch the store.
        deactivate() {
            this._epoch++;
            this.hasLock = false;
            this.inactive = true;
            if (this._retryTimer) clearTimeout(this._retryTimer);
            this._retryTimer = null;
            if (this._onlineHandler) window.removeEventListener('online', this._onlineHandler);
            this._onlineHandler = null;
            if (this._progressTimer) clearInterval(this._progressTimer);
            this._progressTimer = null;
            this.syncQueue = [];
        },

        async fetchProgress() {
            try {
                const res = await fetch(serverData.progressUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) this.progress = await res.json();
            } catch (e) {
                // silent — non-critical
            }
        },

        // The sheet as the server sent it (or an empty one), the starting point before IndexedDB rows apply.
        seedSheet(entry, expanded = false) {
            const server = (serverData.existingSheets || []).find(s => s.entry_id === entry.id);
            const sheet = server
                ? withSaved({ ...server, syncStatus: 'synced', expanded }, server.client_revision ?? 0)
                : emptySheet(entry.id);
            sheet.expanded = expanded;
            return sheet;
        },

        async rebuildFromIndexedDB(epoch) {
            let idbSheets = [];
            try {
                idbSheets = await db.scoresheets
                    .where('entry_id').anyOf(this.entries.map(e => e.id)).toArray();
            } catch (e) {
                console.warn('IDB read failed:', e);
            }
            if (!this.isCurrent(epoch)) return;

            // Rows from before one-row-per-entry (or from two tabs) can hold several sheets of an entry.
            // Keep the one with the highest revision; on a tie prefer one that is not yet synced, then
            // the latest write. Drop the others so they can never resurface.
            const rank = (row) => [row.revision ?? 0, row.syncStatus === 'synced' ? 0 : 1, row.updatedAt ?? ''];
            const newest = {};
            const superseded = [];
            for (const row of idbSheets) {
                const best = newest[row.entry_id];
                if (!best) { newest[row.entry_id] = row; continue; }
                const [a, b] = [rank(row), rank(best)];
                const rowWins = a[0] !== b[0] ? a[0] > b[0] : a[1] !== b[1] ? a[1] > b[1] : a[2] > b[2];
                superseded.push(rowWins ? best.client_uuid : row.client_uuid);
                if (rowWins) newest[row.entry_id] = row;
            }
            if (superseded.length > 0) {
                try {
                    await db.scoresheets.bulkDelete(superseded);
                } catch (e) {
                    console.warn('IDB cleanup failed:', e);
                }
                if (!this.isCurrent(epoch)) return;
            }

            const unsaved = [];
            for (const entry of this.entries) {
                const seed = this.seedSheet(entry, this.sheets[entry.id]?.expanded ?? false);
                const idb = newest[entry.id];
                const unsynced = idb && ['pending', 'queued', 'error'].includes(idb.syncStatus);
                // A synced row older than the server's revision is stale (another device saved since).
                if (!idb || (!unsynced && (idb.revision ?? 0) < seed.revision)) {
                    this.sheets[entry.id] = seed;
                    continue;
                }
                this.sheets[entry.id] = {
                    ...idb,
                    saved: idb.saved ?? { revision: idb.revision ?? 0, content: contentOf(idb) },
                    // Never go below the revision the server already stores.
                    revision: Math.max(idb.revision ?? 0, seed.revision),
                    notice: false,
                    expanded: seed.expanded,
                };
                if (unsynced) this.syncQueue.push(entry.id);
                // Content that was typed but never reached a save: save it now, above the server's revision.
                if (!sameContent(contentOf(this.sheets[entry.id]), this.sheets[entry.id].saved.content)) {
                    unsaved.push(entry.id);
                }
            }
            if (this.syncQueue.length > 0) this.scheduleRetry();
            unsaved.forEach(entryId => this.saveSheet(entryId));
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
            // Claim the save before the first await, so a response to an earlier save cannot mark
            // the sheet synced in the gap between this edit and its request.
            if (!this.hasLock) return;
            const epoch = this._epoch;
            this._sync.waiting[entryId] = (this._sync.waiting[entryId] ?? 0) + 1;
            this._saveVersion[entryId] = (this._saveVersion[entryId] ?? 0) + 1;
            // The revision travels with every request and is stored in IndexedDB, so it survives
            // reloads. The server ignores a request whose revision is not above the stored one.
            this.sheets[entryId].revision = (this.sheets[entryId].revision ?? 0) + 1;
            this.sheets[entryId].notice = false;
            this.sheets[entryId].saved = { revision: this.sheets[entryId].revision, content: contentOf(this.sheets[entryId]) };
            const sheet = this.sheets[entryId];
            const offline = !navigator.onLine;
            const status = offline ? 'queued' : 'pending';
            try {
                await this.putSheet(entryId, { syncStatus: status, updatedAt: new Date().toISOString() });
            } catch (e) {
                console.warn(`IDB write failed (${status}):`, e);
            }
            if (!this.isCurrent(epoch)) return;
            this.sheets[entryId].syncStatus = status;
            if (offline) {
                // No request while offline: the 'online' event sends the queue.
                this._sync.waiting[entryId]--;
                if (!this.syncQueue.includes(entryId)) this.syncQueue.push(entryId);
                this.scheduleRetry();
                return;
            }
            await this.runSync(entryId);
        },

        // Saves of one sheet run strictly one after the other. Overlapping requests can reach the
        // server out of order (a slow draft landing after the submit) and an early response must not
        // mark a sheet synced while a newer save is still waiting.
        syncToServer(entryId) {
            this._sync.waiting[entryId] = (this._sync.waiting[entryId] ?? 0) + 1;
            return this.runSync(entryId);
        },

        // Expects the caller to have counted this save in _syncWaiting already.
        runSync(entryId) {
            const sync = this._sync;
            const run = (sync.chains[entryId] ?? Promise.resolve()).then(async () => {
                const newerWaiting = sync.waiting[entryId] > 1;
                try {
                    // The newest waiting call sends the current sheet state, so older ones can skip.
                    if (!newerWaiting) await this.sendSheet(entryId);
                } finally {
                    sync.waiting[entryId]--;
                }
            });
            sync.chains[entryId] = run.catch(() => {});
            return run;
        },

        async sendSheet(entryId) {
            if (!this.hasLock) return;
            const epoch = this._epoch;
            const sheet = this.sheets[entryId];
            const version = this._saveVersion[entryId];
            const saved = sheet.saved.content;
            // A queued sheet keeps its badge while it retries, so it does not flicker every 5 s.
            if (sheet.syncStatus !== 'queued') this.sheets[entryId].syncStatus = 'syncing';
            let res;
            // A request that never settles (connected but dropping traffic) would block every later
            // save of this sheet. 15 s is well above a normal round trip on a poor mobile connection;
            // the server work takes milliseconds. An abort takes the same path as a lost connection.
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 15000);
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                res = await fetch('/scoresheets', {
                    signal: controller.signal,
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        client_uuid: sheet.client_uuid,
                        client_revision: sheet.saved.revision,
                        entry_id: sheet.entry_id,
                        aroma: saved.aroma,
                        appearance: saved.appearance,
                        flavour: saved.flavour,
                        mouthfeel: saved.mouthfeel,
                        overall: saved.overall,
                        aroma_notes: saved.aroma_notes || null,
                        appearance_notes: saved.appearance_notes || null,
                        flavour_notes: saved.flavour_notes || null,
                        mouthfeel_notes: saved.mouthfeel_notes || null,
                        overall_notes: saved.overall_notes || null,
                        descriptors: saved.descriptors || [],
                        status: saved.status,
                    }),
                });
            } catch (err) {
                // fetch rejects only when the request never got an answer: the sheet is waiting, not broken.
                clearTimeout(timeout);
                if (!this.isCurrent(epoch) || this._saveVersion[entryId] !== version) return;
                console.warn('syncToServer offline', entryId, String(err));
                await this.markUnsynced(entryId, 'queued');
                return;
            }
            try {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const json = await res.json();
                clearTimeout(timeout);
                if (!this.isCurrent(epoch)) return;
                if (json.client_revision > this.sheets[entryId].revision) {
                    this.sheets[entryId].revision = json.client_revision;
                }
                // The sheet changed after this request was built (its newer save is waiting, or it was
                // queued offline): this response covers older data, so leave the sheet's state and
                // its IndexedDB row as that newer save set them.
                if (this._saveVersion[entryId] !== version) return;
                if (json.ignored) {
                    await this.resolveIgnored(entryId, json);
                    return;
                }
                this.sheets[entryId].syncStatus = 'synced';
                // Don't let a stale draft-save response downgrade a submitted sheet
                if (json.status === 'submitted' || this.sheets[entryId].status !== 'submitted') {
                    this.sheets[entryId].status = json.status;
                }
                try {
                    await this.putSheet(entryId, { syncStatus: 'synced' });
                } catch (e) {
                    console.warn('IDB write failed (synced):', e);
                }
            } catch (err) {
                clearTimeout(timeout);
                // A late failure of an older request must not overwrite the state of a newer save.
                if (!this.isCurrent(epoch) || this._saveVersion[entryId] !== version) return;
                // The timeout can also fire while the body is read: that is a lost connection too.
                if (err?.name === 'AbortError') {
                    await this.markUnsynced(entryId, 'queued');
                    return;
                }
                console.error('syncToServer failed', entryId, String(err));
                await this.markUnsynced(entryId, 'error');
            }
        },

        // The server did not apply the request: it already holds this revision or a newer one, saved by
        // a retry that had already landed or by another tab or device. Converge on the server's sheet,
        // but never over what the judge is typing.
        async resolveIgnored(entryId, json) {
            const sheet = this.sheets[entryId];
            const serverContent = contentOf(json.sheet);
            const serverRevision = json.sheet.client_revision ?? sheet.revision;

            if (sameContent(contentOf(sheet), serverContent)) {
                // Same content (a retry of a save the server already applied): keep the live sheet
                // untouched, textarea included, and only record that it is in sync.
                this.sheets[entryId].saved = { revision: serverRevision, content: contentOf(sheet) };
                this.sheets[entryId].syncStatus = 'synced';
            } else if (!sameContent(contentOf(sheet), sheet.saved.content)) {
                // Unsaved edits are waiting for their debounced save, which goes out above the server's revision.
                this.sheets[entryId].syncStatus = 'pending';
                return;
            } else if (document.activeElement?.tagName === 'TEXTAREA' && document.activeElement.dataset.entryId === String(entryId)) {
                // The judge has a notes field open: their view wins. Save it above the server's revision.
                // Not awaited: this runs inside the sheet's save chain, and the new save queues behind it.
                this.saveSheet(entryId);
                return;
            } else {
                // The sheet keeps its own client_uuid: the server matches by (entry, judge) anyway, and
                // changing the uuid would leave the old IndexedDB row behind.
                this.sheets[entryId] = {
                    ...sheet,
                    ...json.sheet,
                    client_uuid: sheet.client_uuid,
                    saved: { revision: serverRevision, content: serverContent },
                    syncStatus: 'synced',
                    notice: true,
                };
            }
            this.sheets[entryId].revision = Math.max(this.sheets[entryId].revision, serverRevision);
            try {
                await this.putSheet(entryId, { syncStatus: 'synced', notice: false });
            } catch (e) {
                console.warn('IDB write failed (ignored):', e);
            }
        },

        // One IndexedDB row per entry: the store is keyed on client_uuid, and two tabs of the same
        // link start with different uuids, so a write removes any other row of the same entry.
        async putSheet(entryId, fields) {
            if (!this.hasLock) return;
            const epoch = this._epoch;
            const row = { ...JSON.parse(JSON.stringify(this.sheets[entryId])), ...fields };
            await db.transaction('rw', db.scoresheets, async () => {
                if (!this.isCurrent(epoch)) return;
                await db.scoresheets.where('entry_id').equals(entryId).and(r => r.client_uuid !== row.client_uuid).delete();
                await db.scoresheets.put(row);
            });
        },

        async markUnsynced(entryId, status) {
            if (!this.hasLock) return;
            this.sheets[entryId].syncStatus = status;
            try {
                await this.putSheet(entryId, { syncStatus: status });
            } catch (e) {
                console.warn(`IDB write failed (${status}):`, e);
            }
            if (!this.syncQueue.includes(entryId)) this.syncQueue.push(entryId);
            this.scheduleRetry();
        },

        scheduleRetry() {
            if (!this.hasLock || this._retryTimer) return;
            this._retryTimer = setTimeout(() => this.flushQueue(), 5000);
        },

        // Retries the queue. While the browser is offline, queued sheets stay put until the
        // 'online' event; sheets that failed with an HTTP error are still retried.
        async flushQueue() {
            if (this._retryTimer) clearTimeout(this._retryTimer);
            this._retryTimer = null;
            const offline = !navigator.onLine;
            const queue = [...this.syncQueue];
            this.syncQueue = offline
                ? queue.filter(id => this.sheets[id]?.syncStatus === 'queued')
                : [];
            // Sheets have their own save chains, so one slow sheet does not hold up the others.
            await Promise.all(queue
                .filter(entryId => !(offline && this.sheets[entryId]?.syncStatus === 'queued'))
                .map(entryId => this.syncToServer(entryId)));
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
            return this.flushQueue();
        },

        openGuide(entryId, sectionKey) {
            const entry = this.entries.find(e => e.id === entryId);
            const titles = labels.sections;

            let srm = null;
            if (sectionKey === 'appearance') {
                const srmMin = entry?.srm_min != null ? Number(entry.srm_min) : null;
                const srmMax = entry?.srm_max != null ? Number(entry.srm_max) : null;
                if (srmMin !== null || srmMax !== null) {
                    const hasBoth = srmMin !== null && srmMax !== null;
                    const single = srmMin !== null ? srmMin : srmMax;
                    const rawPad = hasBoth ? Math.max(3, Math.round((srmMax - srmMin) * 0.3)) : 3;
                    const clampedMin = Math.max(1, (srmMin ?? single) - rawPad);
                    const clampedMax = Math.min(40, (srmMax ?? single) + rawPad);
                    // equalize: trim the unconstrained side to match the clamped side
                    const effectivePad = Math.min((srmMin ?? single) - clampedMin, clampedMax - (srmMax ?? single));
                    const displayMin = (srmMin ?? single) - effectivePad;
                    const displayMax = (srmMax ?? single) + effectivePad;
                    const range = displayMax - displayMin;
                    const pct = (v) => range === 0 ? '50%' : Math.round((v - displayMin) / range * 100) + '%';
                    const maskPct = (v) => range === 0 ? '50%' : Math.round(v / range * 100) + '%';
                    srm = {
                        hasBoth,
                        srmMin,
                        srmMax,
                        leftMinPct: srmMin !== null ? pct(srmMin) : '50%',
                        leftMaxPct: srmMax !== null ? pct(srmMax) : '50%',
                        leftMaskPct: hasBoth ? pct(srmMin) : '0%',
                        rightMaskPct: hasBoth ? maskPct(displayMax - srmMax) : '0%',
                        minFill: srmMin !== null ? srmToHex(srmMin) : '#888888',
                        maxFill: srmMax !== null ? srmToHex(srmMax) : '#888888',
                        gradientStyle: hasBoth
                            ? `top:20px;height:24px;background:linear-gradient(to right,${srmToHex(displayMin)},${srmToHex(displayMax)})`
                            : `top:20px;height:24px;background:${srmToHex(single ?? 1)}`,
                    };
                }
            }

            this.styleGuide = {
                open: true,
                section: titles[sectionKey] ?? sectionKey,
                text: entry?.style_guide?.[sectionKey] ?? 'No descriptor available.',
                srm,
            };
            this.$nextTick(() => { this.$refs.styleGuideScroll.scrollTop = 0; });
        },

        guideLabel(sectionKey, styleName) {
            return labels.guide[sectionKey].replace(':style', () => styleName);
        },

        styleInfoLabel(styleName) {
            return labels.styleInfo.replace(':style', () => styleName);
        },

        closeGuide() {
            this.$refs.styleGuideScroll.scrollTop = 0;
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
                attribution: entry?.style_attribution ?? null,
            };
            this.$nextTick(() => { this.$refs.styleInfoScroll.scrollTop = 0; });
        },


        closeStyleInfo() {
            this.$refs.styleInfoScroll.scrollTop = 0;
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
