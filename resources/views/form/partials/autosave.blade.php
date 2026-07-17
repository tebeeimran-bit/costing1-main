@php
    $autosaveDraftKey = !empty($trackingRevisionId)
        ? 'revision:' . $trackingRevisionId
        : (!empty($costingData?->id) ? 'costing:' . $costingData->id : 'new');
@endphp

<div class="costing-autosave" id="costingAutosave"
    data-draft-key="{{ $autosaveDraftKey }}"
    data-show-url="{{ route('costing.draft.show', absolute: false) }}"
    data-store-url="{{ route('costing.draft.store', absolute: false) }}"
    data-delete-url="{{ route('costing.draft.destroy', absolute: false) }}">
    <div class="costing-autosave-status" aria-live="polite">
        <span class="costing-autosave-dot"></span>
        <span id="costingAutosaveText">Autosave aktif</span>
    </div>
    <div class="costing-draft-recovery" id="costingDraftRecovery" hidden>
        <div>
            <strong>Draft sebelumnya ditemukan</strong>
            <span id="costingDraftSavedAt">Pulihkan perubahan yang belum disimpan?</span>
        </div>
        <div class="costing-draft-actions">
            <button type="button" class="btn btn-secondary btn-sm" id="discardCostingDraft">Buang draft</button>
            <button type="button" class="btn btn-primary btn-sm" id="restoreCostingDraft">Pulihkan draft</button>
        </div>
    </div>
</div>

<style>
    .costing-autosave { margin: 0 0 12px; }
    .costing-autosave-status { display: flex; justify-content: flex-end; align-items: center; gap: 7px; min-height: 24px; color: #64748b; font-size: 12px; font-weight: 600; }
    .costing-autosave-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 3px #dcfce7; }
    .costing-autosave.is-saving .costing-autosave-dot { background: #f59e0b; box-shadow: 0 0 0 3px #fef3c7; }
    .costing-autosave.has-error .costing-autosave-dot { background: #ef4444; box-shadow: 0 0 0 3px #fee2e2; }
    .costing-draft-recovery { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 14px 16px; margin-top: 7px; border: 1px solid #bfdbfe; border-radius: 12px; background: #eff6ff; color: #1e3a5f; }
    .costing-draft-recovery[hidden] { display: none; }
    .costing-draft-recovery strong, .costing-draft-recovery span { display: block; }
    .costing-draft-recovery span { margin-top: 3px; color: #52657d; font-size: 13px; }
    .costing-draft-actions { display: flex; flex: 0 0 auto; gap: 8px; }
    @media (max-width: 720px) { .costing-draft-recovery { align-items: stretch; flex-direction: column; } .costing-draft-actions { justify-content: flex-end; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('costingAutosave');
    const form = document.getElementById('costingForm');
    if (!root || !form) return;

    const statusText = document.getElementById('costingAutosaveText');
    const recovery = document.getElementById('costingDraftRecovery');
    const csrf = form.querySelector('input[name="_token"]')?.value || '';
    const draftKey = root.dataset.draftKey;
    let remoteDraft = null;
    let timer = null;
    let dirty = false;
    let saving = false;
    let restoring = false;
    let submitting = false;

    const setStatus = (text, state = '') => {
        root.classList.toggle('is-saving', state === 'saving');
        root.classList.toggle('has-error', state === 'error');
        statusText.textContent = text;
    };

    const serializableControls = () => Array.from(form.elements).filter((control) => {
        if (!control.name || control.disabled || control.dataset.noAutosave !== undefined) return false;
        return !['_token', 'update_section'].includes(control.name)
            && !['file', 'submit', 'button', 'reset'].includes((control.type || '').toLowerCase());
    });

    const payload = () => serializableControls().map((control) => ({
        name: control.name,
        value: control.value ?? '',
        type: (control.type || control.tagName || 'text').toLowerCase(),
        checked: Boolean(control.checked),
    }));

    const saveDraft = async () => {
        if (!dirty || restoring || submitting) return;
        saving = true;
        setStatus('Menyimpan draft...', 'saving');
        try {
            const response = await fetch(root.dataset.storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    draft_key: draftKey,
                    tracking_revision_id: form.querySelector('[name="tracking_revision_id"]')?.value || null,
                    costing_data_id: form.querySelector('[name="costing_data_id"]')?.value || null,
                    payload: payload(),
                }),
            });
            if (!response.ok) throw new Error('Autosave gagal');
            const result = await response.json();
            dirty = false;
            const time = result.saved_at ? new Date(result.saved_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : 'sekarang';
            setStatus(`Draft tersimpan ${time}`);
        } catch (error) {
            setStatus('Draft belum tersimpan. Coba ubah data lagi.', 'error');
        } finally {
            saving = false;
        }
    };

    const scheduleSave = () => {
        if (restoring || submitting) return;
        dirty = true;
        setStatus('Ada perubahan belum tersimpan', 'saving');
        window.clearTimeout(timer);
        timer = window.setTimeout(saveDraft, 1800);
    };

    const applyDraft = (items) => {
        restoring = true;
        const used = new Map();
        items.forEach((item) => {
            const controls = serializableControls().filter((control) => control.name === item.name);
            const index = used.get(item.name) || 0;
            const control = controls[index];
            used.set(item.name, index + 1);
            if (!control) return;
            if (['checkbox', 'radio'].includes((control.type || '').toLowerCase())) control.checked = Boolean(item.checked);
            else control.value = item.value ?? '';
            control.dispatchEvent(new Event('change', { bubbles: true }));
        });
        restoring = false;
        recovery.hidden = true;
        ['recalculateAllRows', 'calculateTotals', 'refreshMaterialValidationHighlights', 'syncForecastHidden'].forEach((name) => {
            if (typeof window[name] === 'function') window[name]();
        });
        scheduleSave();
        setStatus('Draft dipulihkan', 'saving');
    };

    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);
    form.addEventListener('submit', () => { submitting = true; window.clearTimeout(timer); }, true);

    document.getElementById('restoreCostingDraft')?.addEventListener('click', () => {
        if (remoteDraft?.payload) applyDraft(remoteDraft.payload);
    });
    document.getElementById('discardCostingDraft')?.addEventListener('click', async () => {
        try {
            await fetch(root.dataset.deleteUrl, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ draft_key: draftKey }),
            });
            recovery.hidden = true;
            remoteDraft = null;
            setStatus('Draft dibuang');
        } catch (error) {
            setStatus('Draft gagal dibuang', 'error');
        }
    });

    window.addEventListener('beforeunload', (event) => {
        if (!submitting && (dirty || saving)) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    fetch(`${root.dataset.showUrl}?draft_key=${encodeURIComponent(draftKey)}`, { headers: { 'Accept': 'application/json' } })
        .then((response) => response.ok ? response.json() : Promise.reject())
        .then((result) => {
            if (!result.draft?.payload?.length) return;
            remoteDraft = result.draft;
            const time = result.draft.saved_at ? new Date(result.draft.saved_at).toLocaleString('id-ID') : '';
            document.getElementById('costingDraftSavedAt').textContent = `Tersimpan ${time}. Pulihkan perubahan yang belum disimpan?`;
            recovery.hidden = false;
            setStatus('Draft lama tersedia');
        })
        .catch(() => setStatus('Autosave siap'));
});
</script>
