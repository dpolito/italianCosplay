<?php
$invitations = $data['invitations'] ?? [];
$roles = ['admin' => 'Amministratore', 'editor' => 'Editor', 'viewer' => 'Visualizzatore'];
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$csrfToken = (string) ($_SESSION['csrf_token'] ?? '');
?>
<main class="mx-auto max-w-4xl space-y-6">
    <header>
        <p class="text-sm font-bold uppercase tracking-wide text-amber-800">Gestione inviti</p>
        <h1 class="mt-2 text-3xl font-extrabold text-gray-950">Inviti alle organizzazioni</h1>
        <p class="mt-2 text-gray-700">Controlla le richieste ricevute prima di entrare a far parte di un’organizzazione.</p>
    </header>

    <section class="space-y-4" aria-label="Inviti pendenti">
        <?php if (!$invitations): ?>
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-gray-600 shadow-sm">Non hai inviti pendenti.</div>
        <?php else: ?>
            <?php foreach ($invitations as $invitation): ?>
                <article class="organization-invitation-card rounded-2xl bg-white p-5 shadow-sm md:p-6">
                    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-500">Invito a partecipare a</p>
                            <h2 class="mt-1 text-xl font-extrabold text-gray-950"><?= $h($invitation['organization_name'] ?? 'Organizzazione') ?></h2>
                            <p class="mt-2 text-sm text-gray-700">Ruolo proposto: <strong><?= $h($roles[(string) ($invitation['role'] ?? '')] ?? $invitation['role']) ?></strong></p>
                            <p class="mt-1 text-xs text-gray-500">Scade il <?= $h(date('d/m/Y H:i', strtotime((string) ($invitation['expires_at'] ?? 'now')))) ?></p>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <form class="organization-invitation-action" method="post" action="/dashboard/organization-invitations/accept">
                                <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                                <input type="hidden" name="invitation_id" value="<?= (int) $invitation['id'] ?>">
                                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-green-800 px-4 py-3 text-sm font-bold text-white hover:bg-green-900 sm:w-auto">Accetta</button>
                            </form>
                            <form class="organization-invitation-action" method="post" action="/dashboard/organization-invitations/decline">
                                <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                                <input type="hidden" name="invitation_id" value="<?= (int) $invitation['id'] ?>">
                                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-bold text-gray-800 hover:bg-gray-50 sm:w-auto">Rifiuta</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.organization-invitation-action').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            const card = form.closest('.organization-invitation-card');
            if (!button || !card) return;
            button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new URLSearchParams(new FormData(form)),
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Operazione non riuscita.');
                card.remove();
                const section = document.querySelector('[aria-label="Inviti pendenti"]');
                if (section && !section.querySelector('.organization-invitation-card')) {
                    section.innerHTML = '<div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-gray-600 shadow-sm">Non hai inviti pendenti.</div>';
                }
            } catch (error) {
                button.disabled = false;
                window.alert(error.message || 'Operazione non riuscita.');
            }
        });
    });
});
</script>
