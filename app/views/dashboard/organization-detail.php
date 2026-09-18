<?php
$organization = $organization ?? [];
$role = (string) ($currentUserRole ?? 'viewer');
$roles = ['owner' => 'Proprietario', 'admin' => 'Amministratore', 'editor' => 'Editor', 'viewer' => 'Visualizzatore'];
$invitations = $invitations ?? [];
$invitationStatuses = ['pending' => 'In attesa', 'accepted' => 'Accettato', 'declined' => 'Rifiutato', 'revoked' => 'Revocato', 'expired' => 'Scaduto'];
$canEdit = in_array($role, ['owner', 'admin'], true);
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<main class="mx-auto max-w-6xl space-y-6">
    <div><a href="/dashboard/organizations" class="text-sm font-semibold text-green-900 hover:underline">Torna alle organizzazioni</a><div class="mt-4 flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-bold uppercase tracking-wide text-green-900">Organizzazione</p><h1 class="mt-2 text-3xl font-extrabold text-gray-950"><?= $h($organization['name']) ?></h1></div><span class="rounded-full bg-green-100 px-3 py-1 text-sm font-bold text-green-900"><?= $h($roles[$role] ?? $role) ?></span></div></div>
    <section class="rounded-2xl bg-white p-5 shadow-sm md:p-6"><div class="grid gap-4 sm:grid-cols-3"><div><p class="text-sm text-gray-500">Membri</p><p class="mt-1 text-2xl font-extrabold text-gray-950"><?= count($organization['members'] ?? []) ?></p></div><div><p class="text-sm text-gray-500">Master collegati</p><p class="mt-1 text-2xl font-extrabold text-gray-950"><?= count($organization['masters'] ?? []) ?></p></div><div><p class="text-sm text-gray-500">Stato</p><p class="mt-1 font-bold text-gray-950"><?= $h(['draft' => 'Bozza', 'pending_review' => 'In revisione', 'active' => 'Attiva', 'suspended' => 'Sospesa', 'archived' => 'Archiviata'][$organization['status']] ?? $organization['status']) ?></p></div></div></section>
    <?php if ($canEdit && ($organization['status'] ?? '') !== 'pending_review'): ?><section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm md:p-6"><div data-withdraw-feedback class="hidden rounded-lg px-3 py-2 text-sm" role="status" aria-live="polite"></div><div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="font-bold text-amber-950">Ritira organizzazione</h2><p class="mt-1 text-sm text-amber-900">L’organizzazione resterà salvata, ma non sarà più pubblica e tornerà in revisione.</p></div><form data-withdraw-form method="post" action="/dashboard/organizations/<?= (int) $organization['id'] ?>/withdraw"><input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>"><button class="rounded-lg border border-amber-700 px-4 py-2 font-bold text-amber-950 hover:bg-amber-100">Ritira dalla pubblicazione</button></form></div></section><?php elseif (($organization['status'] ?? '') === 'pending_review'): ?><div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Questa organizzazione è attualmente in revisione e non viene mostrata pubblicamente.</div><?php endif; ?>
    <?php if ($canEdit): ?><section class="rounded-2xl bg-white p-5 shadow-sm md:p-6"><h2 class="text-xl font-bold">Dati organizzazione</h2><div data-organization-feedback class="mt-4 hidden rounded-xl px-4 py-3 text-sm"></div><form data-organization-form data-organization-id="<?= (int) $organization['id'] ?>" action="/dashboard/organizations/<?= (int) $organization['id'] ?>/update" method="POST" enctype="multipart/form-data" class="mt-4 grid gap-4 md:grid-cols-2"><input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>"><div><label class="block text-sm font-semibold" for="dashboard-organization-name">Nome</label><input id="dashboard-organization-name" name="name" required maxlength="180" value="<?= $h($organization['name']) ?>" class="mt-1 w-full rounded-lg border px-3 py-2"></div><div><label class="block text-sm font-semibold" for="dashboard-organization-email">Email</label><input id="dashboard-organization-email" type="email" name="email" maxlength="255" value="<?= $h($organization['email'] ?? '') ?>" class="mt-1 w-full rounded-lg border px-3 py-2"></div><div><label class="block text-sm font-semibold" for="dashboard-organization-website">Sito web</label><input id="dashboard-organization-website" type="url" name="website_url" maxlength="500" value="<?= $h($organization['website_url'] ?? '') ?>" class="mt-1 w-full rounded-lg border px-3 py-2"></div><div class="md:col-span-2"><label for="dashboard-organization-description" class="block text-sm font-semibold">Descrizione</label><div id="dashboard-organization-description" class="mt-1 min-h-48 rounded-lg border bg-white"></div></div><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_public" value="1" <?= !empty($organization['is_public']) ? 'checked' : '' ?>> Pubblica organizzazione</label><div class="md:col-span-2"><button class="rounded-lg bg-green-800 px-5 py-3 font-bold text-white">Salva dati</button></div></form></section><?php endif; ?>
    <section class="rounded-2xl bg-white p-5 shadow-sm md:p-6"><h2 class="text-xl font-bold">Master collegati</h2><?php if ($canEdit): ?><form data-master-form action="/dashboard/organizations/<?= (int) $organization['id'] ?>/masters" method="POST" class="mt-4 flex flex-wrap gap-3"><input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="operation" value="add"><div class="relative min-w-[220px] flex-1"><input type="search" data-master-search autocomplete="off" placeholder="Cerca master" class="w-full rounded-lg border px-3 py-2"><input type="hidden" name="event_master_id" data-master-id required><div data-master-results class="absolute z-10 mt-1 hidden max-h-60 w-full overflow-auto rounded-lg border bg-white shadow-lg"></div></div><select name="role" class="rounded-lg border px-3 py-2"><option value="organizer">Organizzatore</option><option value="co_organizer">Co-organizzatore</option></select><button class="rounded-lg bg-green-800 px-4 py-2 font-bold text-white">Associa</button></form><div data-master-feedback class="mt-3 hidden rounded-lg px-3 py-2 text-sm"></div><?php endif; ?><div class="mt-4 space-y-3"><?php foreach ($organization['masters'] ?? [] as $master): ?><form data-master-form action="/dashboard/organizations/<?= (int) $organization['id'] ?>/masters" method="POST" class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 p-4"><input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="event_master_id" value="<?= (int) $master['event_master_id'] ?>"><a href="/eventi-master/<?= $h($master['slug']) ?>" class="min-w-[220px] flex-1 font-bold text-green-900 hover:underline"><?= $h($master['nome']) ?></a><?php if ($canEdit): ?><select name="role" class="rounded-lg border px-3 py-2 text-sm"><option value="organizer" <?= $master['role'] === 'organizer' ? 'selected' : '' ?>>Organizzatore</option><option value="co_organizer" <?= $master['role'] === 'co_organizer' ? 'selected' : '' ?>>Co-organizzatore</option></select><input type="hidden" name="operation" value="update"><button class="rounded-lg bg-gray-800 px-3 py-2 text-sm font-bold text-white">Salva</button><button type="button" data-remove-master class="rounded-lg bg-red-700 px-3 py-2 text-sm font-bold text-white">Rimuovi</button><?php else: ?><span class="text-sm text-gray-600"><?= $h($master['role'] === 'co_organizer' ? 'Co-organizzatore' : 'Organizzatore') ?></span><?php endif; ?></form><?php endforeach; ?><?php if (empty($organization['masters'])): ?><p class="text-sm text-gray-600">Nessun master collegato.</p><?php endif; ?></div></section>
    <section class="rounded-2xl bg-white p-5 shadow-sm md:p-6"><h2 class="text-xl font-bold">Membri</h2><?php if ($canEdit): ?><form data-member-form action="/dashboard/organizations/<?= (int) $organization['id'] ?>/members" method="POST" class="mt-4 flex flex-wrap gap-3"><input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>"><div class="relative min-w-[220px] flex-1" data-member-typeahead><input type="search" data-member-search autocomplete="off" placeholder="Cerca username o email" class="w-full rounded-lg border px-3 py-2"><input type="hidden" name="user_id" data-member-id required><div data-member-results class="absolute z-10 mt-1 hidden max-h-60 w-full overflow-auto rounded-lg border bg-white shadow-lg"></div></div><select name="role" class="rounded-lg border px-3 py-2"><option value="admin">Amministratore</option><option value="editor">Editor</option><option value="viewer">Visualizzatore</option></select><button class="rounded-lg bg-green-800 px-4 py-2 font-bold text-white">Associa membro</button></form><div data-member-feedback class="mt-3 hidden rounded-lg px-3 py-2 text-sm"></div><?php endif; ?><div class="mt-4 space-y-3"><?php foreach ($organization['members'] ?? [] as $member): ?><form data-member-form action="/dashboard/organizations/<?= (int) $organization['id'] ?>/members" method="POST" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 p-4"><input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="user_id" value="<?= (int) $member['user_id'] ?>"><div><p class="font-bold text-gray-950"><?= $h($member['username']) ?></p><p class="text-sm text-gray-500"><?= $h($member['email']) ?></p></div><?php if ((int) $member['user_id'] === (int) $organization['owner_user_id']): ?><span class="text-sm font-semibold text-gray-700">Proprietario</span><?php elseif ($canEdit): ?><select name="role" class="rounded-lg border px-3 py-2 text-sm"><option value="admin" <?= $member['role'] === 'admin' ? 'selected' : '' ?>>Amministratore</option><option value="editor" <?= $member['role'] === 'editor' ? 'selected' : '' ?>>Editor</option><option value="viewer" <?= $member['role'] === 'viewer' ? 'selected' : '' ?>>Visualizzatore</option></select><select name="status" class="rounded-lg border px-3 py-2 text-sm"><option value="active" selected>Attivo</option><option value="removed">Rimuovi</option></select><button class="rounded-lg bg-gray-800 px-3 py-2 text-sm font-bold text-white">Salva</button><?php else: ?><span class="text-sm font-semibold text-gray-700"><?= $h($roles[$member['role']] ?? $member['role']) ?></span><?php endif; ?></form><?php endforeach; ?></div></section>
    <section class="rounded-2xl bg-white p-5 shadow-sm md:p-6" aria-labelledby="organization-invitations-management-title">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><h2 id="organization-invitations-management-title" class="text-xl font-bold">Inviti all’organizzazione</h2><p class="mt-1 text-sm text-gray-600">Gestisci gli inviti pendenti e consulta lo storico delle richieste.</p></div>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-bold text-gray-700"><?= count($invitations) ?> totali</span>
        </div>
        <div data-invitation-feedback class="mt-4 hidden rounded-lg px-3 py-2 text-sm" role="status" aria-live="polite"></div>
        <?php if (!$invitations): ?>
            <p class="mt-4 rounded-xl border border-dashed border-gray-300 p-5 text-sm text-gray-600">Non ci sono ancora inviti da mostrare.</p>
        <?php else: ?>
            <div class="mt-4 space-y-3">
                <?php foreach ($invitations as $invitation):
                    $invitationStatus = (string) ($invitation['display_status'] ?? $invitation['status'] ?? '');
                    $isPending = $invitationStatus === 'pending';
                    $canResend = in_array($invitationStatus, ['pending', 'expired'], true);
                    $email = $invitation['email'] ?? $invitation['invited_user_email'] ?? '';
                ?>
                    <article class="organization-invitation-management flex flex-col gap-4 rounded-xl border border-gray-200 p-4 md:flex-row md:items-center md:justify-between" data-invitation-id="<?= (int) $invitation['id'] ?>">
                        <div><p class="font-bold text-gray-950"><?= $h($email) ?></p><p class="mt-1 text-sm text-gray-600">Ruolo: <?= $h($roles[(string) ($invitation['role'] ?? '')] ?? $invitation['role']) ?><?php if (!empty($invitation['invited_by_username'] ?? '')): ?> · inviato da <?= $h($invitation['invited_by_username']) ?><?php endif; ?></p><p class="mt-1 text-xs text-gray-500">Creato il <?= $h(date('d/m/Y H:i', strtotime((string) ($invitation['created_at'] ?? 'now')))) ?> · scade il <?= $h(date('d/m/Y H:i', strtotime((string) ($invitation['expires_at'] ?? 'now')))) ?></p></div>
                        <div class="flex flex-wrap items-center gap-2"><span data-invitation-status class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700"><?= $h($invitationStatuses[$invitationStatus] ?? $invitationStatus) ?></span><?php if ($isPending): ?><form class="organization-invitation-management-action" method="post" action="/dashboard/organizations/<?= (int) $organization['id'] ?>/invitations/<?= (int) $invitation['id'] ?>/revoke"><input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>"><button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-bold text-red-800 hover:bg-red-50">Revoca</button></form><?php endif; ?><?php if ($canResend): ?><form class="organization-invitation-management-action" method="post" action="/dashboard/organizations/<?= (int) $organization['id'] ?>/invitations/<?= (int) $invitation['id'] ?>/resend"><input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>"><button class="rounded-lg bg-green-800 px-3 py-2 text-sm font-bold text-white hover:bg-green-900">Reinvia</button></form><?php endif; ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <section class="rounded-2xl bg-white p-5 shadow-sm md:p-6">
        <h2 class="text-xl font-bold">Gestione dedicata dei master</h2>
        <?php if ($canEdit): ?><a href="/dashboard/organizations/<?= (int) $organization['id'] ?>/masters/create" class="mt-3 inline-block rounded-lg bg-green-800 px-4 py-2 font-bold text-white">Proponi nuovo master</a><?php endif; ?>
        <div class="mt-3 flex flex-wrap gap-2">
            <?php foreach ($organization['masters'] ?? [] as $master): ?>
                <a href="/dashboard/organizations/<?= (int) $organization['id'] ?>/masters/<?= (int) $master['event_master_id'] ?>" class="rounded-lg border border-green-200 px-3 py-2 text-sm font-semibold text-green-900 hover:bg-green-50"><?= $h($master['nome']) ?></a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php if ($canEdit): ?>
    <section class="-mt-6 rounded-b-2xl bg-white px-5 pb-5 pt-2 shadow-sm md:px-6 md:pb-6">
        <h3 class="text-lg font-bold">Profilo pubblico</h3>
        <p class="mt-1 text-sm text-gray-600">Questi dati saranno utilizzati anche nella futura pagina pubblica dell’organizzazione.</p>
        <p class="mt-4 text-sm text-gray-600">Usa il pulsante “Salva dati” nella sezione Identità per salvare tutto il profilo.</p>
        <div data-profile-form class="mt-4 grid gap-4 md:grid-cols-2">
            <div><label class="block text-sm font-semibold">Nome legale<input name="legal_name" value="<?= $h($organization['legal_name'] ?? '') ?>" maxlength="220" class="mt-1 w-full rounded-lg border px-3 py-2"></label></div>
            <div><label class="block text-sm font-semibold">Telefono<input name="phone" value="<?= $h($organization['phone'] ?? '') ?>" maxlength="50" class="mt-1 w-full rounded-lg border px-3 py-2"></label></div>
            <?php foreach (['facebook_url' => 'Facebook', 'instagram_url' => 'Instagram', 'tiktok_url' => 'TikTok', 'youtube_url' => 'YouTube'] as $field => $label): ?>
                <div><label class="block text-sm font-semibold"><?= $label ?><input type="url" name="<?= $field ?>" value="<?= $h($organization[$field] ?? '') ?>" class="mt-1 w-full rounded-lg border px-3 py-2"></label></div>
            <?php endforeach; ?>
            <div><label class="block text-sm font-semibold">Logo<input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border px-3 py-2"></label><?php if (!empty($organization['logo_path'])): ?><img src="/public_assets<?= $h($organization['logo_path']) ?>" alt="Logo <?= $h($organization['name']) ?>" class="mt-3 h-20 w-20 rounded-lg object-cover"><?php endif; ?></div>
            <div><label class="block text-sm font-semibold">Cover<input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border px-3 py-2"></label><?php if (!empty($organization['cover_path'])): ?><img src="/public_assets<?= $h($organization['cover_path']) ?>" alt="Cover <?= $h($organization['name']) ?>" class="mt-3 h-20 w-full rounded-lg object-cover"></div><?php endif; ?></div>
            <div class="md:col-span-2"><button type="submit" form="dashboard-organization-form" class="rounded-lg bg-green-800 px-5 py-3 font-bold text-white">Salva dati</button></div>
        </div>
    </section>
    <?php endif; ?>
    <?php if ($canEdit): ?><script>
    const organizationForm = document.querySelector('[data-organization-form]');
    if (organizationForm) {
        organizationForm.id = 'dashboard-organization-form';
        const organizationSection = organizationForm.closest('section');
        const profileSection = document.querySelector('[data-profile-form]')?.closest('section');
        if (organizationSection && profileSection) {
            organizationSection.appendChild(profileSection);
            profileSection.className = 'mt-6 border-t border-gray-200 pt-6';
            const submitArea = organizationForm.querySelector('button[type="submit"]')?.parentElement;
            const submitButton = organizationForm.querySelector('button[type="submit"]');
            if (submitArea && submitButton) {
                profileSection.appendChild(submitArea);
                submitButton.setAttribute('form', organizationForm.id);
                const ajaxButton = document.createElement('button');
                ajaxButton.type = 'submit';
                ajaxButton.hidden = true;
                organizationForm.appendChild(ajaxButton);
            }
        }
        document.querySelectorAll('[data-profile-form] [name]').forEach((field) => field.setAttribute('form', organizationForm.id));
    }
    </script><?php endif; ?>
</main>
<?php if ($canEdit): ?><script>
document.querySelectorAll('[data-withdraw-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button');
        const feedback = document.querySelector('[data-withdraw-feedback]');
        if (button) button.disabled = true;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Operazione non riuscita.');
            if (feedback) { feedback.textContent = result.message; feedback.className = 'mb-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800'; }
            form.remove();
        } catch (error) {
            if (feedback) { feedback.textContent = error.message || 'Operazione non riuscita.'; feedback.className = 'mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800'; }
            if (button) button.disabled = false;
        }
    });
});
</script><?php endif; ?>
<?php if ($canEdit): ?><script src="/public_assets/js/wysiwyg-editor.js"></script><script>
document.addEventListener('DOMContentLoaded', function () {
	WysiwygEditor.init('#dashboard-organization-description', {
		content: <?= json_encode($organization['description'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
		name: 'description',
		placeholder: 'Scrivi la descrizione dell’organizzazione...'
	});
});
</script><?php endif; ?>
<?php if ($canEdit): ?><script>
(function () {
    const feedback = document.querySelector('[data-invitation-feedback]');
    document.querySelectorAll('.organization-invitation-management-action').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button');
            if (button) button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Operazione non riuscita.');
                const card = form.closest('.organization-invitation-management');
                const isResend = form.action.endsWith('/resend');
                if (isResend && card) {
                    const status = card.querySelector('[data-invitation-status]');
                    if (status) {
                        status.textContent = 'In attesa';
                        status.className = 'rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900';
                    }
                    form.remove();
                    const revokeForm = card.querySelector('form[action$="/revoke"]');
                    if (revokeForm) revokeForm.remove();
                } else if (card) {
                    const status = card.querySelector('[data-invitation-status]');
                    if (status) {
                        status.textContent = 'Revocato';
                        status.className = 'rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700';
                    }
                    card.querySelectorAll('form').forEach((item) => item.remove());
                }
                if (feedback) {
                    feedback.textContent = result.message;
                    feedback.className = 'mt-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800';
                }
            } catch (error) {
                if (button) button.disabled = false;
                if (feedback) {
                    feedback.textContent = error.message || 'Operazione non riuscita.';
                    feedback.className = 'mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800';
                }
            }
        });
    });
})();
</script><?php endif; ?>
<?php if ($canEdit): ?><script>
(function () { const box = document.querySelector('[data-member-typeahead]'); if (box) { const input = box.querySelector('[data-member-search]'); const hidden = box.querySelector('[data-member-id]'); const results = box.querySelector('[data-member-results]'); let timer; input.addEventListener('input', () => { hidden.value = ''; clearTimeout(timer); const q = input.value.trim(); results.innerHTML = ''; results.classList.add('hidden'); if (q.length < 2) return; timer = setTimeout(async () => { const response = await fetch('/dashboard/organizations/<?= (int) $organization['id'] ?>/members/search?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }); const payload = await response.json(); results.innerHTML = ''; (payload.users || []).forEach((user) => { const option = document.createElement('button'); option.type = 'button'; option.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-gray-50'; option.textContent = user.username + ' (' + user.email + ')'; option.addEventListener('click', () => { hidden.value = user.id; input.value = user.username; results.classList.add('hidden'); }); results.appendChild(option); }); results.classList.remove('hidden'); }, 250); }); } document.querySelectorAll('[data-member-form]').forEach((form) => { form.addEventListener('submit', async (event) => { event.preventDefault(); const button = form.querySelector('button'); if (button) button.disabled = true; try { const selected = form.querySelector('[data-member-id]'); if (selected && !selected.value) throw new Error('Seleziona un utente dai risultati.'); const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } }); const result = await response.json(); if (!response.ok || !result.success) throw new Error(result.message || 'Operazione non riuscita.'); const feedback = document.querySelector('[data-member-feedback]'); if (feedback) { feedback.textContent = result.message; feedback.className = 'mt-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800'; } } catch (error) { const feedback = document.querySelector('[data-member-feedback]'); if (feedback) { feedback.textContent = error.message; feedback.className = 'mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800'; } } finally { if (button) button.disabled = false; } }); }); })();
</script><?php endif; ?>
<?php if ($canEdit): ?><script>
(function () { const form = document.querySelector('[data-organization-form]'); if (!form) return; const feedback = form.closest('section').querySelector('[data-organization-feedback]'); form.addEventListener('submit', async (event) => { event.preventDefault(); const button = form.querySelector('button'); button.disabled = true; try { const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } }); const result = await response.json(); if (!response.ok || !result.success) throw new Error(result.message || 'Salvataggio non riuscito.'); feedback.textContent = result.message; feedback.className = 'mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800'; } catch (error) { feedback.textContent = error.message; feedback.className = 'mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800'; } finally { button.disabled = false; } }); })();
</script><?php endif; ?>
<?php if ($canEdit): ?><script>
(function () { const box = document.querySelector('[data-master-search]'); if (box) { const hidden = document.querySelector('[data-master-id]'); const results = document.querySelector('[data-master-results]'); let timer; box.addEventListener('input', () => { hidden.value = ''; clearTimeout(timer); const q = box.value.trim(); results.innerHTML = ''; results.classList.add('hidden'); if (q.length < 2) return; timer = setTimeout(async () => { const response = await fetch('/dashboard/organizations/<?= (int) $organization['id'] ?>/masters/search?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }); const payload = await response.json(); (payload.masters || []).forEach((master) => { const option = document.createElement('button'); option.type = 'button'; option.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-gray-50'; option.textContent = master.nome; option.addEventListener('click', () => { hidden.value = master.id; box.value = master.nome; results.classList.add('hidden'); }); results.appendChild(option); }); results.classList.remove('hidden'); }, 250); }); } document.querySelectorAll('[data-master-form]').forEach((form) => { form.addEventListener('submit', async (event) => { event.preventDefault(); const button = event.submitter; if (button) button.disabled = true; try { const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } }); const result = await response.json(); if (!response.ok || !result.success) throw new Error(result.message || 'Operazione non riuscita.'); const feedback = document.querySelector('[data-master-feedback]'); if (feedback) { feedback.textContent = result.message; feedback.className = 'mt-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800'; } } catch (error) { const feedback = document.querySelector('[data-master-feedback]'); if (feedback) { feedback.textContent = error.message; feedback.className = 'mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800'; } } finally { if (button) button.disabled = false; } }); }); document.querySelectorAll('[data-remove-master]').forEach((button) => button.addEventListener('click', async () => { const form = button.closest('form'); if (!form || !window.confirm('Rimuovere il master?')) return; const operation = form.querySelector('[name="operation"]'); if (operation) operation.value = 'remove'; form.requestSubmit(); })); })();
</script><?php endif; ?>
