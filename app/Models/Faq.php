<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Faq extends BaseModel
{
	public function getPublicCategories(string $search = ''): array
	{
		$parameters = [];
		$searchSql = '';
		if ($search !== '') {
			// Use distinct placeholders for each occurrence: with native prepared
			// statements (ATTR_EMULATE_PREPARES = false) a named parameter can be
			// bound only once per query, otherwise PDO throws HY093.
			$searchSql = ' AND (i.question LIKE :search_question OR i.answer LIKE :search_answer OR c.name LIKE :search_category_name)';
			$parameters = [
				'search_question' => '%' . $this->escapeLike($search) . '%',
				'search_answer' => '%' . $this->escapeLike($search) . '%',
				'search_category_name' => '%' . $this->escapeLike($search) . '%',
			];
		}

		$stmt = $this->db->prepare(
			"SELECT c.id AS category_id, c.name AS category_name, c.slug AS category_slug,
			        c.description AS category_description, i.id, i.question, i.answer
			 FROM faq_categories c
			 INNER JOIN faq_items i ON i.category_id = c.id
			 WHERE c.is_active = 1 AND c.deleted_at IS NULL
			   AND i.is_active = 1 AND i.deleted_at IS NULL
			   AND (c.feature_flag_key IS NULL OR EXISTS (
			       SELECT 1 FROM site_feature_flags cff
			       WHERE cff.flag_key = c.feature_flag_key AND cff.is_enabled = 1
			   ))
			   AND (i.feature_flag_key IS NULL OR EXISTS (
			       SELECT 1 FROM site_feature_flags iff
			       WHERE iff.flag_key = i.feature_flag_key AND iff.is_enabled = 1
			   )){$searchSql}
			 ORDER BY c.sort_order ASC, c.name ASC, i.sort_order ASC, i.question ASC"
		);
		$stmt->execute($parameters);

		$categories = [];
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$categoryId = (int) $row['category_id'];
			if (!isset($categories[$categoryId])) {
				$categories[$categoryId] = [
					'id' => $categoryId,
					'name' => $row['category_name'],
					'slug' => $row['category_slug'],
					'description' => $row['category_description'],
					'items' => [],
				];
			}
			$categories[$categoryId]['items'][] = [
				'id' => (int) $row['id'],
				'question' => $row['question'],
				'answer' => $row['answer'],
			];
		}

		return array_values($categories);
	}

	public function getAdminCategories(): array
	{
		return $this->db->query(
			'SELECT c.*, COUNT(i.id) AS item_count
			 FROM faq_categories c
			 LEFT JOIN faq_items i ON i.category_id = c.id AND i.deleted_at IS NULL
			 WHERE c.deleted_at IS NULL
			 GROUP BY c.id ORDER BY c.sort_order ASC, c.name ASC'
		)->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getAdminItems(): array
	{
		return $this->db->query(
			'SELECT i.*, c.name AS category_name
			 FROM faq_items i INNER JOIN faq_categories c ON c.id = i.category_id
			 WHERE i.deleted_at IS NULL AND c.deleted_at IS NULL
			 ORDER BY c.sort_order ASC, i.sort_order ASC, i.question ASC'
		)->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findCategory(int $id): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM faq_categories WHERE id = :id AND deleted_at IS NULL');
		$stmt->execute(['id' => $id]);
		return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
	}

	public function findItem(int $id): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM faq_items WHERE id = :id AND deleted_at IS NULL');
		$stmt->execute(['id' => $id]);
		return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
	}

	public function createCategory(array $data): int
	{
		$stmt = $this->db->prepare('INSERT INTO faq_categories (name, slug, description, feature_flag_key, sort_order, is_active) VALUES (:name, :slug, :description, :feature_flag_key, :sort_order, :is_active)');
		$stmt->execute($data);
		return (int) $this->db->lastInsertId();
	}

	public function updateCategory(int $id, array $data): bool
	{
		$data['id'] = $id;
		$stmt = $this->db->prepare('UPDATE faq_categories SET name = :name, slug = :slug, description = :description, feature_flag_key = :feature_flag_key, sort_order = :sort_order, is_active = :is_active WHERE id = :id AND deleted_at IS NULL');
		return $stmt->execute($data);
	}

	public function createItem(array $data): int
	{
		$stmt = $this->db->prepare('INSERT INTO faq_items (category_id, question, answer, feature_flag_key, sort_order, is_active) VALUES (:category_id, :question, :answer, :feature_flag_key, :sort_order, :is_active)');
		$stmt->execute($data);
		return (int) $this->db->lastInsertId();
	}

	public function updateItem(int $id, array $data): bool
	{
		$data['id'] = $id;
		$stmt = $this->db->prepare('UPDATE faq_items SET category_id = :category_id, question = :question, answer = :answer, feature_flag_key = :feature_flag_key, sort_order = :sort_order, is_active = :is_active WHERE id = :id AND deleted_at IS NULL');
		return $stmt->execute($data);
	}

	public function softDeleteCategory(int $id): bool
	{
		$stmt = $this->db->prepare('UPDATE faq_categories SET deleted_at = NOW(), is_active = 0 WHERE id = :id AND deleted_at IS NULL');
		return $stmt->execute(['id' => $id]);
	}

	public function softDeleteItem(int $id): bool
	{
		$stmt = $this->db->prepare('UPDATE faq_items SET deleted_at = NOW(), is_active = 0 WHERE id = :id AND deleted_at IS NULL');
		return $stmt->execute(['id' => $id]);
	}

	private function escapeLike(string $value): string
	{
		return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
	}
}

