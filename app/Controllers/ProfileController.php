<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use function var_dump;

class ProfileController extends Controller{
	private $userModel;

	public function __construct(){
		$this->userModel = new User();
	}

	public function publicProfile($username){
		$user = $this->userModel->findByUsername($username[0]);
		if(!$user){
			http_response_code(404);
			echo "Profilo non trovato";
			exit;
		}
		$settings = json_decode($user['profile_settings'] ?? '{}', true);
		// sicurezza: default visibilità
		$settings = array_merge([
			'show_email'     => false,
			'show_bio'       => true,
			'show_comune'    => true,
			'show_instagram' => true,
			'show_facebook'  => true,
			'show_tiktok'    => true,
			'show_youtube'   => true,
			'show_nome'   => false,
		], $settings);
		$this->view('profile/public', [
			'user'     => $user,
			'settings' => $settings,
		]);
	}
}
