<?php
namespace App\Models;
use App\Core\Database;
use PDO;

class UserRole
{
	private $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function getAll()
	{
		$sql = "SELECT * FROM user_roles ORDER BY name ASC";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function find($id)
	{
		$stmt = $this->db->prepare("
            SELECT * FROM user_roles WHERE id = :id
        ");

		$stmt->execute([':id' => $id]);

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
}
