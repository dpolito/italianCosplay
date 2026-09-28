<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Event;
use App\Models\User;
use App\Services\CosplayPortfolioService;
use App\Services\FavoriteService;
use function var_dump;

class ProfileController extends Controller{
	private $userModel;
	private CosplayPortfolioService $cosplayPortfolioService;
	private FavoriteService $favoriteService;
	private Event $eventModel;

	public function __construct(){
		$this->userModel = new User();
		$this->cosplayPortfolioService = new CosplayPortfolioService();
		$this->favoriteService = new FavoriteService();
		$this->eventModel = new Event();
	}

	public function getPageNameOverride(): ?string
	{
		return 'profili-pubblici';
	}

	public function index(): void
	{
		$this->requireFeature('enable_public_profiles', 'I profili pubblici sono temporaneamente disattivati.');
		$filters = [
			'q' => trim((string) ($_GET['q'] ?? '')),
			'location' => trim((string) ($_GET['location'] ?? '')),
			'has_bio' => !empty($_GET['has_bio']),
			'sort' => $_GET['sort'] ?? 'recent',
			'direction' => $_GET['direction'] ?? 'DESC',
			'page' => max(1, (int) ($_GET['page'] ?? 1)),
			'per_page' => 12,
		];

		$profiles = $this->userModel->searchPublicProfiles($filters);
		$totalProfiles = $this->userModel->countPublicProfiles($filters);
		$totalPages = max(1, (int) ceil($totalProfiles / $filters['per_page']));

		$baseUrl = URL_ROOT_SITE . '/u';
		$this->view('profile/index', [
			'profiles' => $profiles,
			'filters' => $filters,
			'totalProfiles' => $totalProfiles,
			'totalPages' => $totalPages,
			'currentPage' => $filters['page'],
			'canonicalUrl' => $baseUrl,
			'seo' => [
				'title' => 'Profili cosplay pubblici in Italia | ItalianCosplay',
				'description' => 'Scopri i profili pubblici della community cosplay italiana e filtra per nome, località, bio e visibilità.',
			],
		]);
	}

	public function publicProfile($username){
		$this->requireFeature('enable_public_profiles', 'I profili pubblici sono temporaneamente disattivati.');
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

		$social = !empty($user['social']) ? json_decode($user['social'], true) : [];
		$social = is_array($social) ? array_filter($social) : [];
		if (!empty($social['instagram'])) {
			$social['instagram'] = $this->normalizeInstagramLink($social['instagram']);
		}

		$user['social_links'] = $social;
		$user['avatar'] = !empty($user['avatar']) ? $user['avatar'] : '/public_assets/images/default_avatar.png';
		$user['profile_cover'] = !empty($user['profile_cover']) ? $user['profile_cover'] : null;

		$publicCosplayItems = array_values(array_filter(
			$this->cosplayPortfolioService->getUserPortfolio((int) $user['id']),
			static fn (array $item): bool => !empty($item['is_public'])
		));
		$favoriteEventIds = array_map(
			static fn (array $favorite): int => (int) ($favorite['entity_id'] ?? 0),
			array_filter(
				$this->favoriteService->getUserFavorites((int) $user['id'], 'event'),
				static fn (array $favorite): bool => !empty($favorite['entity_id'])
			)
		);
		$favoriteEvents = $this->eventModel->getEventsByIds($favoriteEventIds, 4);

		$this->view('profile/public', [
			'user'     => $user,
			'settings' => $settings,
			'publicCosplayItems' => $publicCosplayItems,
			'favoriteEvents' => $favoriteEvents,
			'canonicalUrl' => URL_ROOT_SITE . '/u/' . rawurlencode($user['username']),
			'seo' => [
				'title' => '@' . $user['username'] . ' su ItalianCosplay',
				'description' => trim(
					($settings['show_nome'] ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) . ' | ' : '') .
					'Profilo pubblico, bio e link social di @' . $user['username'] . ' su ItalianCosplay.'
				),
			],
		]);
	}

	private function normalizeInstagramLink(?string $value): string
	{
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}

		if (preg_match('#^https?://#i', $value) === 1) {
			return $value;
		}

		return 'https://www.instagram.com/' . ltrim($value, '@/');
	}
}
