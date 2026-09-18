<?php
$invitation = $data['invitation'] ?? [];
$token = (string) ($data['token'] ?? '');
$roles = ['admin' => 'Amministratore', 'editor' => 'Editor', 'viewer' => 'Visualizzatore'];
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$csrfToken = (string) ($_SESSION['csrf_token'] ?? '');
?>
<main class="mx-auto max-w-3xl space-y-6">
    <header>
        <p class="text-sm font-bold uppercase tracking-wide text-amber-800">Invito organizzazione</p>
        <h1 class="mt-2 text-3xl font-extrabold text-gray-950">Vuoi partecipare a questa organizzazione?</h1>
        <p class="mt-2 text-gray-700">Verifica i dettagli e scegli se accettare o rifiutare l’invito.</p>
    </header>

    <section class="rounded-2xl bg-white p-6 shadow-sm md:p-8">
        <p class="text-sm font-semibold text-gray-500">Organizzazione</p>
        <h2 class="mt-1 text-2xl font-extrabold text-gray-950"><?= $h($invitation['organization_name'] ?? 'Organizzazione') ?></h2>
        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl bg-gray-50 p-4"><dt class="text-sm font-semibold text-gray-500">Ruolo</dt><dd class="mt-1 font-bold text-gray-950"><?= $h($roles[(string) ($invitation['role'] ?? '')] ?? $invitation['role']) ?></dd></div>
            <div class="rounded-xl bg-gray-50 p-4"><dt class="text-sm font-semibold text-gray-500">Scadenza</dt><dd class="mt-1 font-bold text-gray-950"><?= $h(date('d/m/Y H:i', strtotime((string) ($invitation['expires_at'] ?? 'now')))) ?></dd></div>
        </dl>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <form class="organization-invitation-action" method="post" action="/dashboard/organization-invitations/accept">
                <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                <input type="hidden" name="token" value="<?= $h($token) ?>">
                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-green-800 px-5 py-3 font-bold text-white hover:bg-green-900 sm:w-auto">Accetta invito</button>
            </form>
            <form class="organization-invitation-action" method="post" action="/dashboard/organization-invitations/decline">
                <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                <input type="hidden" name="token" value="<?= $h($token) ?>">
                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 font-bold text-gray-800 hover:bg-gray-50 sm:w-auto">Rifiuta invito</button>
            </form>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.organization-invitation-action').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            if (!button) return;
            button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new URLSearchParams(new FormData(form)),
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Operazione non riuscita.');
                const section = form.closest('section');
                if (section) {
                    const message = document.createElement('div');
                    message.className = 'rounded-xl border border-green-200 bg-green-50 p-5 font-semibold text-green-900';
                    message.textContent = result.message;
                    section.replaceChildren(message);
                }
            } catch (error) {
                button.disabled = false;
                window.alert(error.message || 'Operazione non riuscita.');
            }
        });
    });
});
</script>
