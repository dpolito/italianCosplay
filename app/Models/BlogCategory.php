<?php

namespace App\Models;

use PDO;

class BlogCategory extends BaseModel
{
	public function __construct()
	{
		parent::__construct();
	}

	public function all(): array
	{
		return $this->db->query("
            SELECT *
            FROM blog_categories
            ORDER BY created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
	}

	public function find(int $id): ?array
	{
		$stmt = $this->db->prepare("
            SELECT *
            FROM blog_categories
            WHERE id = :id
            LIMIT 1
        ");

		$stmt->execute(['id' => $id]);

		return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
	}
	public function findBySlug(string $slug): ?array
	{
		$stmt = $this->db->prepare("
            SELECT *
            FROM blog_categories
            WHERE slug = :slug
            LIMIT 1
        ");

		$stmt->execute(['slug' => $slug]);

		return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
	}

	public function create(array $data): int
	{
		$stmt = $this->db->prepare("
            INSERT INTO blog_categories (name, slug, description, seo_title, seo_description)
            VALUES (:name, :slug, :description, :seo_title, :seo_description)
        ");

		$stmt->execute($data);

		return (int)$this->db->lastInsertId();
	}

	public function update(int $id, array $data): bool
	{
		$data['id'] = $id;

		$stmt = $this->db->prepare("
            UPDATE blog_categories
            SET name = :name,
                slug = :slug,
                description = :description,
                seo_title = :seo_title,
                seo_description = :seo_description,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

		return $stmt->execute($data);
	}

	public function delete(int $id): bool
	{
		$stmt = $this->db->prepare("
            DELETE FROM blog_categories
            WHERE id = :id
        ");

		return $stmt->execute(['id' => $id]);
	}
}
