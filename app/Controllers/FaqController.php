<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\FaqService;

class FaqController extends Controller
{
	private FaqService $faqService;

	public function __construct()
	{
		$this->faqService = new FaqService();
	}

	public function index(): void
	{
		$search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
		$categories = $this->faqService->getPublicFaq($search);
		$this->view('faq/index', [
			'categories' => $categories,
			'search' => $search,
			'canonicalUrl' => URL_ROOT_SITE . '/faq',
			'noindex' => $search !== '',
		]);
	}

	public function getPageNameOverride(): string
	{
		return 'faq';
	}
}

