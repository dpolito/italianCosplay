<?php
namespace App\Models;
use PDO;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function str_replace;
use function strtolower;

class Guest extends BaseModel
{

	public function __construct()
	{
		parent::__construct();
	}

	public function search($q)
	{
		$stmt = $this->db->prepare("
            SELECT id, name
            FROM guests
            WHERE name LIKE :q
            ORDER BY name ASC
            LIMIT 10
        ");

		$stmt->execute(['q' => "%$q%"]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function create($name)
	{
		$slug = strtolower(str_replace(' ', '-', $name));

		$stmt = $this->db->prepare("
            INSERT INTO guests (name, slug)
            VALUES (:name, :slug)
        ");

		$stmt->execute([
			'name' => $name,
			'slug' => $slug
		]);

		return $this->db->lastInsertId();
	}
	public function getGuests(int $eventId): array
	{

		$stmt = $this->db->prepare("
        SELECT g.id, g.name
        FROM guests g
        INNER JOIN event_guests eg
            ON eg.guest_id = g.id
        WHERE eg.event_id = ?
        ORDER BY g.name
    ");

		$stmt->execute([$eventId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function saveGuests(
		int $eventId,
		array $guestIds
	): void {

		// elimina vecchie relazioni
		$stmt =$this->db->prepare("
        DELETE FROM event_guests
        WHERE event_id = ?
    ");

		$stmt->execute([$eventId]);

		// inserisce nuove relazioni
		$stmt = $this->db->prepare("
        INSERT INTO event_guests
        (
            event_id,
            guest_id
        )
        VALUES
        (
            ?,
            ?
        )
    ");

		foreach ($guestIds as $guestId) {

			$stmt->execute([
				$eventId,
				(int)$guestId
			]);
		}
	}

	public function removeFromEvent(int $eventId, int $guestId): bool
	{
		$stmt = $this->db->prepare('DELETE FROM event_guests WHERE event_id = :event_id AND guest_id = :guest_id');
		$stmt->execute([':event_id' => $eventId, ':guest_id' => $guestId]);
		return $stmt->rowCount() > 0;
	}

	public function getAll(): array
	{
		$stmt = $this->db->prepare("
		SELECT bp.*
		FROM guests bp
		ORDER BY bp.created_at DESC
	");

		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function findBySlug(string $slug): ?array
	{
		$stmt = $this->db->prepare("
        SELECT *
        FROM guests
        WHERE slug = ?
        LIMIT 1
    ");

		$stmt->execute([$slug]);

		$result = $stmt->fetch(\PDO::FETCH_ASSOC);

		return $result ?: null;
	}
	public function getEvents(int $guestId): array
	{
		$sql = "
        SELECT e.id, e.titolo, e.slug, e.data_inizio, e.data_fine, e.luogo,
               i.path as immagine
        FROM events e
        INNER JOIN event_guests eg ON eg.event_id = e.id
        LEFT JOIN entity_images i 
            ON i.entity_type = 'event' 
            AND i.entity_id = e.id 
            AND i.is_primary = 1
         	AND i.preset='medium'
            AND i.deleted_at IS NULL
        WHERE eg.guest_id = :guest_id
          AND e.deleted_at IS NULL
        ORDER BY e.data_inizio DESC
    ";

		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':guest_id', $guestId, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}
	public function countEvents(int $guestId): int
	{
		$sql = "
        SELECT COUNT(*) as total
        FROM event_guests
        WHERE guest_id = :guest_id
    ";

		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':guest_id', $guestId, PDO::PARAM_INT);
		$stmt->execute();

		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		return (int) ($result['total'] ?? 0);
	}
	public function AdminCreate(array $data)
	{
		$slug = strtolower(str_replace(' ', '-', $data['name']));

		$stmt = $this->db->prepare("
            INSERT INTO guests (name, slug, bio,website, instagram, tiktok, youtube, created_at)
            VALUES (:name, :slug, :bio,:website, :instagram, :tiktok, :youtube, :created_at)
        ");

		$stmt->execute([
			'name' => $data['name'],
			'slug' => $data['slug'],
			'bio' => $data['bio'],
			'website' => $data['website'],
			'instagram' => $data['instagram'],
			'tiktok' => $data['tiktok'],
			'youtube' => $data['youtube'],
			'created_at' => $data['created_at'],
		]);

		return $this->db->lastInsertId();
	}
	public function find(int $id): ?array
	{
		$stmt = $this->db->prepare("
		SELECT *
		FROM guests
		WHERE id = :id
		LIMIT 1
	");

		$stmt->execute([
			'id' => $id
		]);

		return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
	}
	public function update(int $id, array $data): bool
	{
		$stmt = $this->db->prepare("
		UPDATE guests
		SET
			name = :name,
			slug = :slug,
			bio = :bio,
			website = :website,
			instagram = :instagram,
			tiktok = :tiktok,
			youtube = :youtube,
			updated_at = :updated_at
		WHERE id = :id
	");

		return $stmt->execute([
			'name' => $data['name'],
			'slug' => $data['slug'],
			'bio' => $data['bio'],
			'website' => $data['website'],
			'instagram' => $data['instagram'],
			'tiktok' => $data['tiktok'],
			'youtube' => $data['youtube'],
			'updated_at' => $data['updated_at'],
			'id' => $data['id']
		]);
	}
	public function delete(int $id): bool
	{
		$stmt = $this->db->prepare("DELETE from guests WHERE id = :id ");

		return $stmt->execute([
			'id' => $id
		]);
	}

}
