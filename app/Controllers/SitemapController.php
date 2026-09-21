<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Comune;
use App\Models\Event;
use App\Models\EventMaster;
use App\Repositories\OrganizationRepository;
use App\Models\Provincia;
use App\Models\Regione;
use function date;
use function header;
use function strtotime;
use const PHP_EOL;

class SitemapController extends Controller
{
	private Event $eventModel;
	private Regione $regioneModel;
	private Provincia $provinciaModel;
	private Comune $comuneModel;
	private BlogPost $blogPostModel;
	private BlogCategory $blogCategoryModel;
	private EventMaster $eventMasterModel;
	private OrganizationRepository $organizationRepository;

	public function __construct()
	{
		$this->eventModel = new Event();
		$this->regioneModel = new Regione();
		$this->provinciaModel = new Provincia();
		$this->comuneModel = new Comune();
		$this->blogPostModel = new BlogPost();
		$this->blogCategoryModel = new BlogCategory();
		$this->eventMasterModel = new EventMaster();
		$this->organizationRepository = new OrganizationRepository();
	}
	public function index()
	{
		header('Content-Type: application/xml; charset=utf-8');

		$base = 'https://www.italiancosplay.it';

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		// =========================
		// STATIC SITEMAP
		// =========================
		$xml .= $this->addSitemap($base . '/sitemap-static.xml');

		// =========================
		// EVENTS SITEMAP
		// =========================
		$xml .= $this->addSitemap($base . '/sitemap-events.xml');
		$xml .= $this->addSitemap($base . '/sitemap-events-master.xml');
		$xml .= $this->addSitemap($base . '/sitemap-organizations.xml');

		// =========================
		// GEOGRAPHY SITEMAP
		// =========================

		$xml .= $this->addSitemap($base . '/sitemap-locations.xml');
		$xml .= $this->addSitemap($base . '/sitemap-blog-categorie.xml');
		$xml .= $this->addSitemap($base . '/sitemap-blog-post.xml');


		$xml .= '</sitemapindex>';

		echo $xml;
	}

	public function organizations()
	{
		header('Content-Type: application/xml; charset=utf-8');
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
		foreach ($this->organizationRepository->findAllPublic() as $organization) {
			$url = 'https://www.italiancosplay.it/organizzazioni/' . rawurlencode((string) $organization['slug']);
			$lastmod = !empty($organization['updated_at']) ? date('Y-m-d', strtotime($organization['updated_at'])) : date('Y-m-d');
			$xml .= $this->addUrl($url, '0.6', $lastmod);
		}
		$xml .= '</urlset>';
		echo $xml;
	}

	private function addSitemap(string $loc): string
	{
		return "  <sitemap>\n" .
			"    <loc>{$loc}</loc>\n" .
			"    <lastmod>" . date('Y-m-d') . "</lastmod>\n" .
			"  </sitemap>\n";
	}
	public function static()
	{
		header('Content-Type: application/xml; charset=utf-8');

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		$xml .= $this->addUrl('https://www.italiancosplay.it/', '1.0');
		$xml .= $this->addUrl('https://www.italiancosplay.it/eventi-cosplay', '0.9');
		$xml .= $this->addUrl('https://www.italiancosplay.it/eventi-cosplay-mese', '0.6');
		$xml .= $this->addUrl('https://www.italiancosplay.it/eventi-cosplay-weekend', '0.6');
		foreach ($this->eventModel->getAvailableYears() as $year) {
			$lastmod = !empty($year['last_modified']) ? date('Y-m-d', strtotime($year['last_modified'])) : date('Y-m-d');
			$xml .= $this->addUrl('https://www.italiancosplay.it/eventi-cosplay-' . $year['year'], '0.7', $lastmod);
		}
		foreach ($this->eventModel->getAvailableMonths() as $month) {
			$xml .= $this->addUrl('https://www.italiancosplay.it/eventi-cosplay-mese/' . $month['slug'], '0.6');
		}
		$xml .= $this->addUrl('https://www.italiancosplay.it/agenda-cosplay', '0.7');
		$xml .= $this->addUrl('https://www.italiancosplay.it/cosplan', '0.7');
		$xml .= $this->addUrl('https://www.italiancosplay.it/organizzatori-eventi-cosplay', '0.7');
		$xml .= $this->addUrl('https://www.italiancosplay.it/segnala-evento-cosplay', '0.6');
		$xml .= $this->addUrl('https://www.italiancosplay.it/privacy', '0.3');
		$xml .= $this->addUrl('https://www.italiancosplay.it/cookies', '0.3');
		$xml .= $this->addUrl('https://www.italiancosplay.it/faq', '0.6');
		$xml .= $this->addUrl('https://www.italiancosplay.it/blog', '0.9');

		$xml .= '</urlset>';

		echo $xml;
	}
	public function events()
	{
		$events = $this->eventModel->getApprovedEvents(null, null, null);

		header('Content-Type: application/xml; charset=utf-8');

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($events as $event) {

			$url = 'https://www.italiancosplay.it/eventi-cosplay/' . $event['slug'];

			$lastmod = !empty($event['Modify_date'])
				? date('Y-m-d', strtotime($event['Modify_date']))
				: date('Y-m-d');

			$xml .= $this->addUrl($url, '0.8', $lastmod);
		}

		$xml .= '</urlset>';

		echo $xml;
	}

	public function events_master()
	{
		header('Content-Type: application/xml; charset=utf-8');

		$eventMasters = $this->eventMasterModel->getPublic();

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($eventMasters as $eventMaster) {
			$eventCount = $this->eventMasterModel->countEvents((int) $eventMaster['id']);
			if ($eventCount < 2) {
				continue;
			}

			$url = 'https://www.italiancosplay.it/eventi-master/' . $eventMaster['slug'];
			$lastmod = !empty($eventMaster['updated_at'])
				? date('Y-m-d', strtotime($eventMaster['updated_at']))
				: date('Y-m-d');

			$xml .= $this->addUrl($url, '0.6', $lastmod);
		}

		$xml .= '</urlset>';

		echo $xml;
	}
	public function blog_categorie()
	{
		$categorie = $this->blogCategoryModel->all();

		header('Content-Type: application/xml; charset=utf-8');

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($categorie as $categoria) {

			$url = 'https://www.italiancosplay.it/blog/categoria/' . $categoria['slug'];

			$lastmod = !empty($categoria['updated_at'])
				? date('Y-m-d', strtotime($categoria['updated_at']))
				: date('Y-m-d');

			$xml .= $this->addUrl($url, '0.8', $lastmod);
		}

		$xml .= '</urlset>';

		echo $xml;
	}
	public function blog_post()
	{
		$posts = $this->blogPostModel->getAllPublished();

		header('Content-Type: application/xml; charset=utf-8');

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($posts as $post) {

			$url = 'https://www.italiancosplay.it/blog/' . $post['slug'];

			$lastmod = !empty($post['updated_at'])
				? date('Y-m-d', strtotime($post['updated_at']))
				: date('Y-m-d');

			$xml .= $this->addUrl($url, '0.8', $lastmod);
		}

		$xml .= '</urlset>';

		echo $xml;
	}

	public function locations()
	{
		$regioni = $this->regioneModel->getAll();

		header('Content-Type: application/xml; charset=utf-8');

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($regioni as $regione) {

			// Regione
			$xml .= $this->addUrl(
				'https://www.italiancosplay.it/eventi-cosplay/' . $regione['slug'],
				'0.6'
			);

			$province = $this->provinciaModel->getByRegioneId($regione['id']);

			foreach ($province as $provincia) {

				// Provincia
				$xml .= $this->addUrl(
					'https://www.italiancosplay.it/eventi-cosplay/' . $regione['slug'] . '/' . $provincia['slug'],
					'0.5'
				);

				$comuni = $this->comuneModel->getAll($provincia['id']);

				foreach ($comuni as $comune) {

					// Comune
					$xml .= $this->addUrl(
						'https://www.italiancosplay.it/eventi-cosplay/' . $regione['slug'] . '/' . $provincia['slug'] . '/' . $comune['slug'],
						'0.4'
					);
				}
			}
		}

		$xml .= '</urlset>';

		echo $xml;
	}

	/**
	 * Helper per aggiungere un URL nel formato corretto
	 */
	private function addUrl($loc, $priority = '0.5', $lastmod = null)
	{
		$xml  = "  <url>\n";
		$xml .= "    <loc>{$loc}</loc>\n";
		if ($lastmod) {
			$xml .= "    <lastmod>{$lastmod}</lastmod>\n";
		}
		$xml .= "    <changefreq>weekly</changefreq>\n";
		$xml .= "    <priority>{$priority}</priority>\n";
		$xml .= "  </url>\n";
		return $xml;
	}
}
