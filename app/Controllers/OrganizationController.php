<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\OrganizationService;

class OrganizationController extends Controller
{
	private OrganizationService $organizationService;

	public function __construct()
	{
		$this->organizationService = new OrganizationService();
	}

	public function show(array $params): void
	{
		$slug = trim((string) ($params[0] ?? ''));
		$organization = $this->organizationService->findPublicBySlug($slug);
		if (!$organization) {
			http_response_code(404);
			$this->view('errors/404');
			return;
		}

		$canonicalUrl = URL_ROOT_SITE . '/organizzazioni/' . rawurlencode((string) $organization['slug']);
		$organizationImage = !empty($organization['cover_path']) ? (preg_match('/^https?:\\/\\//i', (string) $organization['cover_path']) ? $organization['cover_path'] : '/public_assets/' . ltrim((string) $organization['cover_path'], '/')) : '';
		$organizationName = trim((string) ($organization['name'] ?? 'Organizzazione'));
		$plainDescription = trim(preg_replace('/\\s+/', ' ', strip_tags(html_entity_decode((string) ($organization['description'] ?? ''), ENT_QUOTES, 'UTF-8'))));
		$metaDescription = $plainDescription !== ''
			? mb_strimwidth($plainDescription, 0, 155, '...')
			: 'Scopri ' . $organizationName . ': organizzazione, eventi master ed edizioni cosplay pubblicate su ItalianCosplay.';
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => 'Organizzazioni', 'url' => URL_ROOT_SITE . '/organizzazioni'],
			['label' => $organization['name'], 'url' => $canonicalUrl],
		];
		$this->view('organizations/show', [
			'organization' => $organization,
			'breadcrumbs' => $breadcrumbs,
			'canonicalUrl' => $canonicalUrl,
			'pageTitle' => $organizationName . ' | Organizzazione cosplay | ItalianCosplay',
			'organization_meta_title' => $organizationName . ' | Organizzazione cosplay | ItalianCosplay',
			'organization_meta_description' => $metaDescription,
			'organization_image' => $organizationImage,
		]);
	}

	public function index(): void
	{
		$canonicalUrl = URL_ROOT_SITE . '/organizzazioni';
		$this->view('organizations/index', [
			'organizations' => $this->organizationService->getPublicOrganizations(),
			'canonicalUrl' => $canonicalUrl,
			'pageTitle' => 'Organizzazioni cosplay in Italia | ItalianCosplay',
		]);
	}
}
