<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\EventReportPrivacyAcceptanceRepository;
use App\Repositories\PrivacyPolicyRepository;
use RuntimeException;

class EventReportConsentService
{
	private PrivacyPolicyRepository $privacyPolicyRepository;
	private EventReportPrivacyAcceptanceRepository $eventReportPrivacyAcceptanceRepository;

	public function __construct()
	{
		$this->privacyPolicyRepository = new PrivacyPolicyRepository();
		$this->eventReportPrivacyAcceptanceRepository = new EventReportPrivacyAcceptanceRepository();
	}

	public function storeAcceptance(int $eventId, ?string $ipAddress = null, ?string $userAgent = null): void
	{
		$privacyVersion = $this->privacyPolicyRepository->getLatestActiveVersion()
			?? $this->privacyPolicyRepository->getLatestVersion();

		if ($privacyVersion === null) {
			throw new RuntimeException('Nessuna versione privacy disponibile.');
		}

		$this->eventReportPrivacyAcceptanceRepository->recordAcceptance([
			'event_id' => $eventId,
			'privacy_policy_version_id' => (int) $privacyVersion['id'],
			'ip_address' => $ipAddress,
			'user_agent' => $userAgent,
		]);
	}
}
