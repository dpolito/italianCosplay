<?php
namespace App\Models;

use PDO;

class BlogPost  extends BaseModel
{
	public function __construct()
	{
		parent::__construct();
	}
	public function getRelatedPostPublishedWithCover(int $post_id, int $category_id){
		$stmt = $this->db->prepare("SELECT 
    p.*,

    -- views totali (SUM)
    COALESCE(v.total_views, 0) AS total_views,

    -- cover image (subquery deterministica)
    i.path AS featured_image,
    i.width,
    i.height

FROM blog_posts p

/* =======================
   VIEWS (SUM in subquery)
======================= */
LEFT JOIN (
    SELECT 
        blog_post_id,
        SUM(views) AS total_views
    FROM blog_post_views
    GROUP BY blog_post_id
) v ON v.blog_post_id = p.id

/* =======================
   COVER IMAGE (subquery)
======================= */
LEFT JOIN entity_images i 
    ON i.id = (
        SELECT i2.id
        FROM entity_images i2
        WHERE i2.entity_id = p.id
          AND i2.entity_type = 'blog_post'
          AND i2.preset = 'thumb'
          AND i2.is_primary = 1
          AND i2.deleted_at IS NULL
        ORDER BY i2.id ASC
        LIMIT 1
    )

WHERE p.categoria_id = :categoria_id
AND p.id != :post_id
AND p.status = 'published'
AND p.deleted_at IS NULL

ORDER BY p.created_at DESC
LIMIT 5;");
		$stmt->bindValue(':categoria_id', $category_id, \PDO::PARAM_INT);
		$stmt->bindValue(':post_id', $post_id, \PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);



	}



	public function getPublishedWithCover(int $limit = 12, int $offset = 0): array
	{
		$stmt = $this->db->prepare("
        SELECT 
            p.*,
            COALESCE(v.views, 0) AS total_views,

            COALESCE(
                i_medium.path,
                i_thumb.path,
                i_original.path,
                '/assets/default-blog.webp'
            ) AS cover_image,

            COALESCE(
                i_medium.width,
                i_thumb.width,
                i_original.width,
                100
            ) AS width_image,

            COALESCE(
                i_medium.height,
                i_thumb.height,
                i_original.height,
                100
            ) AS height_image,

            COALESCE(
                i_medium.alt_text,
                i_thumb.alt_text,
                i_original.alt_text,
                p.titolo
            ) AS alt_text_image

        FROM blog_posts p

        LEFT JOIN (
            SELECT blog_post_id, SUM(views) AS views
            FROM blog_post_views
            GROUP BY blog_post_id
        ) v ON v.blog_post_id = p.id

        LEFT JOIN entity_images i_medium
            ON i_medium.entity_type = 'blog_post'
            AND i_medium.entity_id = p.id
            AND i_medium.is_primary = 1
            AND i_medium.preset = 'medium'
            AND i_medium.deleted_at IS NULL

        LEFT JOIN entity_images i_thumb
            ON i_thumb.entity_type = 'blog_post'
            AND i_thumb.entity_id = p.id
            AND i_thumb.is_primary = 1
            AND i_thumb.preset = 'thumb'
            AND i_thumb.deleted_at IS NULL

        LEFT JOIN entity_images i_original
            ON i_original.entity_type = 'blog_post'
            AND i_original.entity_id = p.id
            AND i_original.is_primary = 1
            AND i_original.preset = 'original'
            AND i_original.deleted_at IS NULL

        WHERE p.status = 'published'
          AND p.deleted_at IS NULL

        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ");

		$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}
	public function getByCategoryWithCover(string $slug, int $limit = 12, int $offset = 0): array
	{
		$stmt = $this->db->prepare("
		SELECT 
			p.*,
			 COALESCE(v.views, 0) AS total_views,
			COALESCE(
				i_medium.path,
				i_thumb.path,
				i_original.path,
				'/assets/default-blog.webp'
			) AS cover_image,
COALESCE(
				i_medium.width,
				i_thumb.width,
				i_original.width,
			'100'
			) AS width_image,
			COALESCE(
				i_medium.height,
				i_thumb.height,
				i_original.height,
			'100'
			) AS height_image,
			COALESCE(
				i_medium.alt_text,
				i_thumb.alt_text,
				i_original.alt_text,
				'Immagine articolo blog'
			) AS alt_text_image

		FROM blog_posts p
		    LEFT JOIN (
            SELECT blog_post_id, SUM(views) AS views
            FROM blog_post_views
            GROUP BY blog_post_id
        ) v ON v.blog_post_id = p.id

		JOIN blog_categories pc 
			ON pc.id = p.categoria_id
		/* IMMAGINI */
		LEFT JOIN entity_images i_medium
			ON i_medium.entity_type = 'blog_post'
			AND i_medium.entity_id = p.id
			AND i_medium.is_primary = 1
			AND i_medium.preset = 'medium'
			AND i_medium.deleted_at IS NULL

		LEFT JOIN entity_images i_thumb
			ON i_thumb.entity_type = 'blog_post'
			AND i_thumb.entity_id = p.id
			AND i_thumb.is_primary = 1
			AND i_thumb.preset = 'thumb'
			AND i_thumb.deleted_at IS NULL

		LEFT JOIN entity_images i_original
			ON i_original.entity_type = 'blog_post'
			AND i_original.entity_id = p.id
			AND i_original.is_primary = 1
			AND i_original.preset = 'original'
			AND i_original.deleted_at IS NULL

		WHERE pc.slug = :slug
		  AND p.status = 'published'
		  AND p.deleted_at IS NULL

		ORDER BY p.created_at DESC

		LIMIT :limit OFFSET :offset
	");

		$stmt->bindValue(':slug', $slug, \PDO::PARAM_STR);
		$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

		$stmt->execute();

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}



	public function countPublished(?string $categorySlug = null): int
	{
		if ($categorySlug) {
			$stmt = $this->db->prepare("
			SELECT COUNT(*) 
			FROM blog_posts p
			JOIN blog_categories c ON c.id = p.categoria_id
			WHERE p.status = 'published'
			  AND p.deleted_at IS NULL
			  AND c.slug = :slug
		");

			$stmt->execute(['slug' => $categorySlug]);
		} else {
			$stmt = $this->db->prepare("
			SELECT COUNT(*) 
			FROM blog_posts
			WHERE status = 'published'
			  AND deleted_at IS NULL
		");

			$stmt->execute();
		}

		return (int) $stmt->fetchColumn();
	}

	public function getPublished(int $limit = 10): array
	{
		$stmt = $this->db->prepare("
            SELECT * 
            FROM blog_posts
            WHERE status = 'published'
              AND deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT :limit
        ");

		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function getAllPublished(): array
	{
		$stmt = $this->db->prepare("
            SELECT * 
            FROM blog_posts
            WHERE status = 'published'
              AND deleted_at IS NULL
            ORDER BY created_at DESC
        ");
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findBySlug(string $slug): ?array
	{
		$stmt = $this->db->prepare("
            SELECT * 
            FROM blog_posts
            WHERE slug = :slug AND status = 'published' AND deleted_at IS NULL
            LIMIT 1
        ");

		$stmt->execute(['slug' => $slug]);

		return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
	}

	public function getByCategory(string $slug): array
	{
		$stmt = $this->db->prepare("
            SELECT p.*
            FROM blog_posts p
            JOIN blog_post_category pc ON pc.post_id = p.id
            JOIN blog_categories c ON c.id = pc.category_id
            WHERE c.slug = :slug
              AND p.status = 'published'
              AND p.deleted_at IS NULL
            ORDER BY p.created_at DESC
        ");

		$stmt->execute(['slug' => $slug]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function getAll(): array
	{
		$stmt = $this->db->prepare("
		SELECT bp.*,COALESCE(v.views, 0) AS views
		FROM blog_posts bp
		 LEFT JOIN (
            SELECT blog_post_id, SUM(views) AS views
            FROM blog_post_views
            GROUP BY blog_post_id
        ) v ON v.blog_post_id = bp.id
		
		
		WHERE bp.deleted_at IS NULL
		ORDER BY bp.created_at DESC
	");

		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function find(int $id): ?array
	{
		$stmt = $this->db->prepare("
		SELECT *
		FROM blog_posts
		WHERE id = :id
		AND deleted_at IS NULL
		LIMIT 1
	");

		$stmt->execute([
			'id' => $id,
		]);

		return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
	}
	public function create(array $data): int
	{
		$stmt = $this->db->prepare("
		INSERT INTO blog_posts
		(
			titolo,
			slug,
			excerpt,
			contenuto,
			meta_title,
			meta_description,
			categoria_id,
			related_event_id,
			status,
			user_id,
		 published_at
		)
		VALUES
		(
			:titolo,
			:slug,
			:excerpt,
			:contenuto,
			:meta_title,
			:meta_description,
			:categoria_id,
			:related_event_id,
			:status,
			:user_id,
		 :published_at
		)
	");

		$stmt->execute([
			'titolo' => $data['titolo'],
			'slug' => $data['slug'],
			'excerpt' => $data['excerpt'],
			'contenuto' => $data['contenuto'],
			'meta_title' => $data['meta_title'],
			'meta_description' => $data['meta_description'],
			'categoria_id' => $data['categoria_id'],
			'related_event_id' => $data['related_event_id'],
			'status' => $data['status'],
			'user_id' => $data['user_id'],
			'published_at' => $data['published_at'],
		]);

		return (int)$this->db->lastInsertId();
	}
	public function update(int $id, array $data): bool
	{
		$stmt = $this->db->prepare("
		UPDATE blog_posts
		SET
			titolo = :titolo,
			slug = :slug,
			excerpt = :excerpt,
			contenuto = :contenuto,
			meta_title = :meta_title,
			meta_description = :meta_description,
			categoria_id = :categoria_id,
			related_event_id = :related_event_id,
			status = :status,
			published_at=:published_at
		WHERE id = :id
		  AND deleted_at IS NULL
	");

		return $stmt->execute([
			'id' => $id,
			'titolo' => $data['titolo'],
			'slug' => $data['slug'],
			'excerpt' => $data['excerpt'],
			'contenuto' => $data['contenuto'],
			'meta_title' => $data['meta_title'],
			'meta_description' => $data['meta_description'],
			'categoria_id' => $data['categoria_id'],
			'related_event_id' => $data['related_event_id'],
			'status' => $data['status'],
			'published_at' => $data['published_at'],
		]);
	}
	public function delete(int $id, ?int $deletedBy = null, ?string $reason = null): bool
	{
		$stmt = $this->db->prepare("
		UPDATE blog_posts
		SET deleted_at = NOW(),
		    deleted_by = :deleted_by,
		    deletion_reason = :deletion_reason
		WHERE id = :id
		  AND deleted_at IS NULL
	");

		return $stmt->execute([
			'id' => $id,
			'deleted_by' => $deletedBy,
			'deletion_reason' => $reason,
		]);
	}
	public function slugExists(string $slug, ?int $excludeId = null): bool
	{
		$sql = "
		SELECT COUNT(*)
		FROM blog_posts
		WHERE slug = :slug
		AND deleted_at IS NULL
	";

		$params = [
			'slug' => $slug,
		];

		if ($excludeId) {
			$sql .= " AND id != :id";
			$params['id'] = $excludeId;
		}

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return (int)$stmt->fetchColumn() > 0;
	}
	public function getImages(int $postId): array
	{
		$stmt = $this->db->prepare("
	        SELECT i.*, iv.*
	FROM entity_images ei
	JOIN images i ON i.id = ei.image_id
	LEFT JOIN image_variants iv ON iv.image_id = i.id
	WHERE ei.entity_type = 'blog'
	AND ei.entity_id = :id
	AND ei.deleted_at IS NULL
	    ");

		$stmt->execute(['id' => $postId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

}
