<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Event;
use PDO;

class ProvinceCorrelateService
{
	protected $db;

    public function __construct()
    {
	    $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Restituisce:
     * - province della regione
     * - numero eventi approvati e futuri
     * - prossimo evento della provincia
     *
     * @param string $slugRegione Es: lombardia
     * @return array
     */
    public function getProvinceCorrelate(string $slugRegione): array
    {
        $sql = "
            SELECT
                p.id,
                p.nome AS provincia_nome,
                p.slug AS provincia_slug,

                COUNT(DISTINCT e.id) AS totale_eventi,

                MIN(e.data_inizio) AS prossimo_evento_data

            FROM province p

            INNER JOIN regioni r
                ON r.id = p.regione_id

            LEFT JOIN events e
                ON e.provincia_id = p.id
                AND e.approvato = 1
                AND e.data_inizio >= CURDATE()

            WHERE r.slug = :slug_regione

            GROUP BY
                p.id,
                p.nome,
                p.slug

            ORDER BY
                totale_eventi DESC,
                prossimo_evento_data ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':slug_regione' => $slugRegione
        ]);

        $province = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($province as &$provincia) {

            $provincia['prossimo_evento'] = $this->getProssimoEventoProvincia(
                (int)$provincia['id']
            );
        }

        return $province;
    }

    /**
     * Recupera il prossimo evento della provincia
     */
    private function getProssimoEventoProvincia(int $provinciaId): ?array
    {
        $sql = "
            SELECT
                e.id,
                e.titolo,
                e.slug,
                e.data_inizio,
                c.nome AS comune_nome

            FROM events e

            LEFT JOIN comuni c
                ON c.id = e.comune_id

            WHERE e.provincia_id = :provincia_id
              AND e.approvato = 1
              AND e.deleted_at IS NULL
              AND e.data_inizio >= CURDATE()

            ORDER BY e.data_inizio ASC

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':provincia_id' => $provinciaId
        ]);

        $evento = $stmt->fetch(PDO::FETCH_ASSOC);

        return $evento ?: null;
    }
}
