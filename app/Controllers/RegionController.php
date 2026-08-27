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

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$regioni = $this->regionModel->getAll();
		$page = max((int) ($_GET['page'] ?? 1), 1);
		$perPage = (int) ($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}
		$search = trim($_GET['search'] ?? '');
		$sort = $_GET['sort'] ?? 'nome';
		$direction = strtolower($_GET['direction'] ?? 'asc');
		$allowedSorts = ['id', 'nome'];
		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'nome';
		}
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'asc';
		}

		$regioni = array_values(array_filter($regioni, static function (array $regione) use ($search): bool {
			if ($search === '') {
				return true;
			}
			return str_contains(strtolower(implode(' ', $regione)), strtolower($search));
		}));
		usort($regioni, static function (array $left, array $right) use ($sort, $direction): int {
			$result = strcmp((string) ($left[$sort] ?? ''), (string) ($right[$sort] ?? ''));
			return $direction === 'asc' ? $result : -$result;
		});

		$total = count($regioni);
		$pages = max((int) ceil($total / $perPage), 1);
		$page = min($page, $pages);
		$slice = array_slice($regioni, ($page - 1) * $perPage, $perPage);

		$data = array_map(static function (array $regione): array {
			return [
				'id' => $regione['id'],
				'nome' => $regione['nome'],
				'_links' => [
					'edit' => '/admin/regioni/edit/' . $regione['id'],
					'delete' => '/admin/regioni/delete/' . $regione['id'],
				],
			];
		}, $slice);

		echo json_encode([
			'success' => true,
			'data' => $data,
			'meta' => ['page' => $page, 'pages' => $pages, 'total' => $total],
		]);
		exit();
	}

	public function detail($params): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$id = (int) ($params[0] ?? 0);
		$regione = $this->regionModel->find($id);

		if (!$regione) {
			echo json_encode(['success' => false, 'message' => 'Regione non trovata']);
			exit();
		}

		echo json_encode([
			'success' => true,
			'data' => $regione,
		]);
		exit();
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
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Token CSRF non valido.']);
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
				header('Content-Type: application/json; charset=utf-8');
				http_response_code(422);
				echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
				return;
			}

			if ($this->regionModel->update($id, $data)) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode([
					'success' => true,
					'message' => 'Regione aggiornata con successo!',
					'id' => (int) $id,
				]);
				exit();


			} else {
				header('Content-Type: application/json; charset=utf-8');
				http_response_code(500);
				echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento della regione.']);
				exit();
			}
		} else {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(405);
			echo json_encode(['success' => false, 'message' => 'Metodo non consentito.']);
			exit();
		}
	}
	/**
	 * Elimina un evento (ADMIN).
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function delete($params)
	{
		header('Content-Type: application/json; charset=utf-8');

		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'ID regione non valido.']);
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$this->validateCsrfToken()) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Token CSRF non valido.']);
			exit();
		}

		$event = $this->regionModel->find($id);
		if (!$event) {
			http_response_code(404);
			echo json_encode(['success' => false, 'message' => 'Regione non trovata.']);
			exit();
		}
		if ($this->regionModel->delete($id)) {
			echo json_encode(['success' => true, 'message' => 'Regione eliminata con successo!']);
			exit();
		}

		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione della regione.']);
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
