<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\BlogAnalyticsService;
use App\Services\EventFeedService;
use App\Services\FavoriteService;
use App\Services\ImageService;
use function ceil;
use function header;
use function var_dump;
use const URL_ROOT_SITE;

class BlogController extends Controller
{
	private BlogPost $blogPost;
	private BlogCategory $blogCategoryModel;
	private ImageService $imageService;
	private BlogAnalyticsService  $blogAnalyticsService;
	private EventFeedService $eventFeedService;
	private FavoriteService $favoriteService;

	public function __construct()
	{
		$this->blogPost = new BlogPost();
		$this->blogCategoryModel = new BlogCategory();
		$this->imageService = new ImageService($this->blogPost->getDbConnection());
		$this->blogAnalyticsService = new BlogAnalyticsService();
		$this->eventFeedService = new EventFeedService();
		$this->favoriteService = new FavoriteService();
	}

	// 📄 LISTA ARTICOLI
	public function index($params)
	{
		$this->requireFeature('enable_blog', 'Il blog è temporaneamente disattivato.');
		$page = isset($params[0]) ? (int) $params[0] : 1;
		$limit = 12;
		$offset = ($page - 1) * $limit;


		$posts = $this->blogPost->getPublishedWithCover($limit, $offset);
		$total_posts = $this->blogPost->countPublished();
		$total_pages = (int) ceil($total_posts / $limit);

		$categorie = $this->blogCategoryModel->all();
		$canonicalUrl = URL_ROOT_SITE . '/blog';
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => "Blog", 'url' => URL_ROOT_SITE . '/blog'],
		];

		$this->view('blog/index', [
			'posts' => $posts,
			'page' => $page,
			'total_pages' => $total_pages,
			'page_title' => 'Blog ItalianCosplay',
			'categories' => $categorie,
			'total_posts' => $total_posts,
			'canonicalUrl' => $canonicalUrl,
			'breadcrumbs' => $breadcrumbs,
		]);
	}

	// 📄 SINGOLO ARTICOLO
	public function show($params)
	{
		$this->requireFeature('enable_blog', 'Il blog è temporaneamente disattivato.');
		$slug = $params[0] ?? null;
		if (!$slug) {
			Session::setFlash('error', 'Slug evento non valido.');
			header('Location: /blog');
			exit();
		}
		$post = $this->blogPost->findBySlug($slug);
		if (!$post) {
			http_response_code(404);
			$this->view('errors/404');
			return;
		}
		$cover = $this->imageService->getPrimary('blog_post', $post['id'], 'large');
		$categoria = $this->blogCategoryModel->find($post['categoria_id']);
		$canonicalUrl = URL_ROOT_SITE . '/blog/' . $slug;
		$postRelated = $this->blogPost->getRelatedPostPublishedWithCover($post['id'], $post['categoria_id']);
		$relatedEvents = $this->eventFeedService->getWeekend()['top3'];
		//var_dump($relatedEvents);
		$this->blogAnalyticsService->trackView($post['id']);
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => "Blog", 'url' => URL_ROOT_SITE . '/blog'],
			['label' => $categoria['name'] ?? 'Categoria', 'url' => URL_ROOT_SITE . '/blog/categoria/' . ($categoria['slug'] ?? $slug)],
			['label' => $post['titolo'], 'url' =>URL_ROOT_SITE . '/blog/'.$slug],
		];
		$content = html_entity_decode(strip_tags($post['contenuto']), ENT_QUOTES | ENT_HTML5, 'UTF-8');

		preg_match_all('/[\p{L}\p{N}]+(?:[-\'][\p{L}\p{N}]+)*/u', $content, $matches);

		$wordCount = count($matches[0]);
		$post['reading_time'] = max(1, ceil($wordCount / 200));
		$isFavorited = !empty($_SESSION['user_id'])
			? $this->favoriteService->isFavorited((int) $_SESSION['user_id'], 'blog_post', (int) $post['id'])
			: false;

		$this->view('blog/show', [
			'post' => $post,
			'page_title' => $post['titolo'],
			'cover' => $cover,
			'categoria' => $categoria,
			'breadcrumbs' => $breadcrumbs,
			'canonicalUrl' => $canonicalUrl,
			'relatedPosts' => $postRelated,
			'relatedEvents' => $relatedEvents,
			'wordCount' => $wordCount,
			'isFavorited' => $isFavorited,
			'favoriteEntityType' => 'blog_post',
		]);
	}

	// 📂 CATEGORIA
	public function category($params)
	{
		$this->requireFeature('enable_blog', 'Il blog è temporaneamente disattivato.');
		$slug = $params[0] ?? null;
		if (!$slug) {
			Session::setFlash('error', 'Slug evento non valido.');
			header('Location: /blog');
			exit();
		}
		$page = isset($params[1]) ? (int) $params[1] : 1;
		$limit = 12;
		$offset = ($page - 1) * $limit;


		$posts = $this->blogPost->getByCategoryWithCover($slug, $limit, $offset);
		$total_posts = $this->blogPost->countPublished($slug);
		$total_pages = (int) ceil($total_posts / $limit);
		$categoria = $this->blogCategoryModel->findBySlug($slug);
		$canonicalUrl = URL_ROOT_SITE . '/blog/categoria/' . $slug;
		$categorie = $this->blogCategoryModel->all();

		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => "Blog", 'url' => URL_ROOT_SITE . '/blog'],
			['label' => $categoria['name'], 'url' => URL_ROOT_SITE . '/blog/categoria/'.$categoria['slug']],
		];

		$this->view('blog/category', [
			'posts' => $posts,
			'page' => $page,
			'total_pages' => $total_pages,
			'category_slug' => $slug,
			'page_title' => 'Categoria Blog',
			'categories' => $categorie,
			'total_posts' => $total_posts,
			'active_category' =>    $slug,
			'categoria' => $categoria,
			'canonicalUrl' => $canonicalUrl,
			'breadcrumbs' => $breadcrumbs,
		]);
	}
}
