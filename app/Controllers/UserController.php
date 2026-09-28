<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Session;
use App\Models\User;

class UserController extends Controller
{
	private User $userModel;
	private $uploadDir = APP_ROOT . '/public_assets/uploads/avatar/';

	public function __construct()
	{
		$this->userModel = new User();
	}

	/**
	 * 👤 PROFILO PUBBLICO
	 * URL: /user/{username}
	 */
	public function show($params)
	{
		$username = $params[1] ?? null;

		if (!$username) {
			Session::setFlash('error', 'Username non valido.');
			return;
		}

		$userModel = new User();
		$user = $this->userModel->findByUsername($username);
		$social = [];
		if (!empty($user['social'])) {
			$social = json_decode($user['social'], true); // true = array associativo
		}
		if (!empty($social['instagram'])){
			$user['instagram'] = $social['instagram'];
		}
		if (!empty($social['tiktok'])){
			$user['tiktok'] = $social['tiktok'];
		}
		if (!empty($social['youtube'])){
			$user['youtube'] = $social['youtube'];
		}

		if (!empty($social['facebook'])){
			$user['facebook'] = $social['facebook'];
		}


		if (!$user) {
			Session::setFlash('error', 'Utente non valido.');
			return;
		}

		// fallback avatar
		if (empty($user['avatar'])) {
			$user['avatar'] = '/public_assets/images/default_avatar.png';
		}
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => 'Profili', 'url' => URL_ROOT_SITE . '/user'],
			['label' => $username, 'url' => URL_ROOT_SITE . '/user/'.$username],
		];

		// 👉 carico la view
		$data = [
			'user' => $user,
			'breadcrumbs' => $breadcrumbs,
			'canonicalUrl' => URL_ROOT_SITE . '/user/'. $username,
		];

		$this->view('user/show', $data);
	}
}
