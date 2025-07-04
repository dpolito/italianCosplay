<?php

class BaseModel
{
	protected $table;
	protected $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function all()
	{
		$stmt = $this->db->query("SELECT * FROM {$this->table}");
		return $stmt->fetchAll();
	}

	public function find($id)
	{
		$stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
		$stmt->execute(['id' => $id]);
		return $stmt->fetch();
	}
}
