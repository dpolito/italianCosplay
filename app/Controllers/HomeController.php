<?php
namespace App\Controllers;
// Non è necessario includere il modello Event qui a meno che tu non voglia
// recuperare eventi reali per la homepage. Per ora, useremo dati statici.
use App\Core\Controller;
use App\Models\Event;
use App\Models\BlogPost;
use App\Models\TipoEvento;
use App\Services\EventFeedService;
use App\Services\CookiePolicyService;
use App\Services\PrivacyPolicyService;
use App\Services\ImageService;

class HomeController extends Controller
{
	private Event $eventModel;
	private BlogPost $blogPostModel;
	private TipoEvento $tipoEventoModel;
	private EventFeedService $eventFeedService;
	private CookiePolicyService $cookiePolicyService;
	private PrivacyPolicyService $privacyPolicyService;
	private ImageService $imageService;

	public function __construct()
	{
		$this->eventModel = new Event();
		$this->blogPostModel = new BlogPost();
		$this->tipoEventoModel = new TipoEvento();
		$this->eventFeedService = new EventFeedService();
		$this->cookiePolicyService = new CookiePolicyService();
		$this->privacyPolicyService = new PrivacyPolicyService();
		$this->imageService = new ImageService($this->eventModel->getDbConnection());
		// Definisci il percorso base per gli eventi qui per evitare duplicazioni
		// CORREZIONE QUI: Rimuovi lo slash finale da URL_ROOT prima di concatenare
	}

	/**
	 * Mostra la homepage del sito.
	 */
	public function index()
	{
		$showEvents = $this->featureEnabled('enable_events');
		$showBlog = $this->featureEnabled('enable_blog');
		$feed = $this->eventFeedService->getHomeFeed();
		$latestPosts = $showBlog ? $this->blogPostModel->getPublishedWithCover(3, 0) : [];
		$latestEvents = [];
		$upcomingEvents = [];
		$approvedEventsCount = 0;
		$publishedPostsCount = 0;

		if ($showEvents) {
			$approvedEventsCount = $this->eventModel->countApprovedEvents();
			$latestEvents = $this->eventModel->getLatestApprovedEvents(8);
			$upcomingEvents = $this->eventModel->getApprovedEvents(null, null, null, 12);

			usort($upcomingEvents, static function (array $left, array $right): int {
				return strtotime($left['data_fine'] ?? '9999-12-31') <=> strtotime($right['data_fine'] ?? '9999-12-31');
			});

			foreach ($latestEvents as $index => &$event) {
				$cover = $this->imageService->getPrimary('event', (int) $event['id']);

				if (!empty($cover['path'])) {
					$event['immagine'] = '/public_assets/' . ltrim((string) $cover['path'], '/');
					$event['immagine_width'] = $cover['width'] ?? null;
					$event['immagine_height'] = $cover['height'] ?? null;
				} elseif (empty($event['immagine'])) {
					$event['immagine'] = '';
					$event['immagine_width'] = null;
					$event['immagine_height'] = null;
				}

				$event['lazy'] = ($index >= 3);
			}
			unset($event);

			foreach ($upcomingEvents as $index => &$event) {
				$cover = $this->imageService->getPrimary('event', (int) $event['id']);

				if (!empty($cover['path'])) {
					$event['immagine'] = '/public_assets/' . ltrim((string) $cover['path'], '/');
					$event['immagine_width'] = $cover['width'] ?? null;
					$event['immagine_height'] = $cover['height'] ?? null;
				} elseif (empty($event['immagine'])) {
					$event['immagine'] = '';
					$event['immagine_width'] = null;
					$event['immagine_height'] = null;
				}

				$event['lazy'] = ($index >= 3);
			}
			unset($event);
		}

		if ($showBlog) {
			$publishedPostsCount = $this->blogPostModel->countPublished();
		}

		$this->view('home/index', [
			'top' => $feed['top'],
			'trending' => $feed['trending'],
			'new' => $feed['new'],
			'feed' => $feed['feed'],
			'latestPosts' => $latestPosts,
			'latestEvents' => $latestEvents,
			'upcomingEvents' => $upcomingEvents,
			'approvedEventsCount' => $approvedEventsCount,
			'publishedPostsCount' => $publishedPostsCount,
			'showEvents' => $showEvents,
			'showBlog' => $showBlog,
			'canonicalUrl' => URL_ROOT_SITE . '/',
		]);
	}

	/**
	 * Mostra la homepage del sito.
	 */
	public function privacy()
	{
		$policy = $this->privacyPolicyService->getCurrentPolicy();

		$this->view('home/privacy', [
			'policy' => $policy,
		]);
	}

	/**
	 * Mostra la homepage del sito.
	 */
	public function cookies()
	{
		$policy = $this->cookiePolicyService->getCurrentPolicy();

		$this->view('home/cookie', [
			'policy' => $policy,
		]);
	}
}
