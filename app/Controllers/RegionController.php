<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Regione;
use App\Models\User;
use App\Services\EventAnalyticsService;
use function array_merge;
use function bin2hex;
use function file_exists;
use function filter_var;
use function header;
use function implode;
use function is_numeric;
use function random_bytes;
use function strlen;
use function strtotime;
use function trim;
use function unlink;
use function var_dump;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;
use const FILTER_VALIDATE_URL;
use const UPLOAD_ERR_NO_FILE;
use const URL_ROOT;

class RegionController extends Controller
{
	private Regione  $regionModel;
	public function __construct()
	{
		$this->regionModel = new Regione();
		// CSRF token
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}
	/**
	 * Mostra tutti gli eventi (approvati e non) per l'amministratore.
	 */
	public function all()
	{
		$regioni = $this->regionModel->getAll();

		$this->view('admin/regioni/all', ['regioni' => $regioni, 'csrf_token' => $_SESSION['csrf_token']], 'admin');
	}

	public function edit($params): void
	{
		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID regione non valido.');
			header('Location: ' . URL_ROOT . '/admin/regioni/all');
			exit();
		}
		$region = $this->regionModel->find($id);

		if (!$region) {
			http_response_code(404);
			echo "Regione non trovata";
			return;
		}


		$this->view('admin/regioni/edit', [
			'regione' => $region,
			'csrf_token' => $_SESSION['csrf_token']
		], 'admin');
	}
	public function update($params)
	{
		if (!$this->validateCsrfToken()) {
			$id = $params[0] ?? null;
			header('Location: /admin/regioni/edit/' . $id);
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$id = $params[0] ?? null;
			if (!$id || !is_numeric($id)) {
				Session::setFlash('error', 'ID regione non valido.');
				header('Location: ' . URL_ROOT . '/admin/regioni/all');
				exit();
			}

			$regione = $this->regionModel->find($id);
			if (!$regione) {
				Session::setFlash('error', 'Regione non trovata.');
				header('Location: ' . URL_ROOT . '/admin/regioni/all');
				exit();
			}

			$data = [
				'nome' => trim($_POST['nome'] ?? null),
				'seo_title' => trim($_POST['seo_title'] ?? null),
				'seo_description' => trim($_POST['seo_description'] ?? ''),
				'intro_html' => trim($_POST['intro_html'] ?? ''),
			];

			$errors = [];

			if (empty($data['nome'])) $errors[] = 'Il nome è obbligatorio.';
			if (strlen($data['nome']) > 100) $errors[] = 'Il nome è troppo lungo (max 100 caratteri).';


			if (!empty($errors)) {
				Session::setFlash('error', implode('<br>', $errors));

				$this->view('admin/regioni/edit', array_merge($data, ['regione' => $regione, 'csrf_token' => $_SESSION['csrf_token']]), 'admin');
				return;
			}

			if ($this->regionModel->update($id, $data)) {
				Session::setFlash('success', 'Regione aggiornata con successo!');
				header("Location: /admin/regioni/edit/$id");


			} else {
				Session::setFlash('error', 'Errore durante l\'aggiornamento dell\'evento.');
				$this->view('admin/regioni/edit', array_merge($data, ['regione' => $regione, 'csrf_token' => $_SESSION['csrf_token']]), 'admin');
			}
		} else {
			header('Location: ' . URL_ROOT . '/admin/regioni/all');
			exit();
		}
	}
	/**
	 * Elimina un evento (ADMIN).
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function delete($params)
	{
		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: /admin/regioni/all');
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$this->validateCsrfToken()) {
			header('Location: /admin/regioni/all');
			exit();
		}

		$event = $this->regionModel->find($id);
		if (!$event) {
			Session::setFlash('error', 'Regione non trovata.');
			header('Location: /admin/regioni/all');
			exit();
		}
		if ($this->regionModel->delete($id)) {
			Session::setFlash('success', 'Regione eliminata con successo!');
		} else {
			Session::setFlash('error', 'Errore durante l\'eliminazione della regione.');
		}
		header('Location: /admin/regioni/all');
		exit();
	}

	private function validateCsrfToken()
	{
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
			Session::setFlash('error', 'Errore di sicurezza: richiesta non valida (CSRF).');
			return false;
		}
		return true;
	}
}
