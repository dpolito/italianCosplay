<h1>Dashboard Amministratore</h1>
<p>Benvenuto nell'area amministrativa, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</p>

<ul>
	<li><a href="/admin/users">Gestione Utenti</a></li>
	<!-- Aggiungi altri link di gestione qui -->
</ul>

<?php
// Visualizza i messaggi flash
if (isset($_SESSION['flash_messages'])) {
	foreach ($_SESSION['flash_messages'] as $type => $message) {
		echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
	}
	unset($_SESSION['flash_messages']); // Pulisci i messaggi flash dopo averli visualizzati
}
?>

<style>
	.flash-message {
		padding: 10px;
		margin-bottom: 15px;
		border-radius: 5px;
	}
	.flash-message.success {
		background-color: #d4edda;
		color: #155724;
		border: 1px solid #c3e6cb;
	}
	.flash-message.error {
		background-color: #f8d7da;
		color: #721c24;
		border: 1px solid #f5c6cb;
	}
	.flash-message.info {
		background-color: #d1ecf1;
		color: #0c5460;
		border: 1px solid #bee5eb;
	}
</style>
