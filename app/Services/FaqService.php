<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Faq;
use InvalidArgumentException;

class FaqService
{
	private Faq $faq;

	public function __construct()
	{
		$this->faq = new Faq();
	}

	public function getPublicFaq(string $search = ''): array
	{
		return $this->faq->getPublicCategories(mb_substr(trim($search), 0, 100));
	}

	public function getAdminData(): array
	{
		return ['categories' => $this->faq->getAdminCategories(), 'items' => $this->faq->getAdminItems()];
	}

	public function getAdminCategories(): array
	{
		return $this->faq->getAdminCategories();
	}

	public function getAdminItems(): array
	{
		return $this->faq->getAdminItems();
	}

	public function findCategory(int $id): ?array { return $this->faq->findCategory($id); }
	public function findItem(int $id): ?array { return $this->faq->findItem($id); }

	public function saveCategory(?int $id, array $input): int
	{
		$name = trim((string) ($input['name'] ?? ''));
		if ($name === '' || mb_strlen($name) > 150) throw new InvalidArgumentException('Inserisci un nome categoria valido (massimo 150 caratteri).');
		$slug = $this->slugify((string) ($input['slug'] ?? $name));
		if ($slug === '') throw new InvalidArgumentException('Inserisci uno slug valido.');
		$data = [
			'name' => $name, 'slug' => $slug,
			'description' => $this->nullableText($input['description'] ?? null, 500),
			'feature_flag_key' => $this->nullableText($input['feature_flag_key'] ?? null, 100),
			'sort_order' => max(0, (int) ($input['sort_order'] ?? 0)),
			'is_active' => isset($input['is_active']) ? 1 : 0,
		];
		if ($id !== null) { $this->faq->updateCategory($id, $data); return $id; }
		return $this->faq->createCategory($data);
	}

	public function saveItem(?int $id, array $input): int
	{
		$categoryId = (int) ($input['category_id'] ?? 0);
		$question = trim((string) ($input['question'] ?? ''));
		$answer = trim((string) ($input['answer'] ?? ''));
		if (!$this->faq->findCategory($categoryId)) throw new InvalidArgumentException('Seleziona una categoria valida.');
		if ($question === '' || mb_strlen($question) > 255) throw new InvalidArgumentException('Inserisci una domanda valida (massimo 255 caratteri).');
		if ($answer === '') throw new InvalidArgumentException('Inserisci una risposta.');
		if (mb_strlen($answer) > 20000) throw new InvalidArgumentException('La risposta non può superare 20.000 caratteri.');
		$data = [
			'category_id' => $categoryId, 'question' => $question, 'answer' => $answer,
			'feature_flag_key' => $this->nullableText($input['feature_flag_key'] ?? null, 100),
			'sort_order' => max(0, (int) ($input['sort_order'] ?? 0)),
			'is_active' => isset($input['is_active']) ? 1 : 0,
		];
		if ($id !== null) { $this->faq->updateItem($id, $data); return $id; }
		return $this->faq->createItem($data);
	}

	public function deleteCategory(int $id): bool { return $this->faq->softDeleteCategory($id); }
	public function deleteItem(int $id): bool { return $this->faq->softDeleteItem($id); }

	private function slugify(string $value): string
	{
		$value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
		return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');
	}

	private function nullableText(mixed $value, int $maxLength): ?string
	{
		$value = trim((string) $value);
		return $value === '' ? null : mb_substr($value, 0, $maxLength);
	}
}
