<?php

namespace App\Services;

use PDO;

class BlogImageService
{
	private ImageService $imageService;
	private PDO $db;

	public function __construct(PDO $db)
	{
		$this->db = $db;

		// CONTEXT BLOG (cartella separata)
		$this->imageService = new ImageService($db, 'blog');
	}

	/**
	 * UPLOAD COVER IMAGE POST BLOG
	 */
	public function uploadCover(array $file, int $postId, string $title, string $slug): ?int
	{
		$imageId = $this->imageService->upload(
			$file,
			'blog',
			$postId,
			$title,
			$slug
		);

		if (!$imageId) {
			return null;
		}

		$this->linkToPost($postId, $imageId);

		return $imageId;
	}

	/**
	 * LINK BLOG POST → IMAGE (cover_image_id + pivot)
	 */
	private function linkToPost(int $postId, int $imageId): void
	{
		$stmt = $this->db->prepare("
			UPDATE blog_posts
			SET cover_image_id = :image_id
			WHERE id = :post_id
		");

		$stmt->execute([
			'image_id' => $imageId,
			'post_id' => $postId
		]);

		// opzionale pivot generica (se la usi)
		$stmt = $this->db->prepare("
			INSERT INTO entity_images (entity_type, entity_id, image_id)
			VALUES ('blog', :post_id, :image_id)
		");

		$stmt->execute([
			'post_id' => $postId,
			'image_id' => $imageId
		]);
	}

	/**
	 * GENERA URL SEO IMMAGINE (OG / TWITTER)
	 */
	public function getOgImageUrl(int $postId): ?string
	{
		$stmt = $this->db->prepare("
			SELECT iv.path
			FROM blog_posts bp
			JOIN images i ON i.id = bp.cover_image_id
			JOIN image_variants iv ON iv.image_id = i.id
			WHERE bp.id = :id
			  AND iv.preset = 'large'
			LIMIT 1
		");

		$stmt->execute(['id' => $postId]);

		$path = $stmt->fetchColumn();

		return $path ? '/public_assets' . $path : null;
	}

	/**
	 * FALLBACK SEO IMAGE (se non esiste cover)
	 */
	public function getDefaultOgImage(): string
	{
		return '/public_assets/assets/og-default.jpg';
	}
}
