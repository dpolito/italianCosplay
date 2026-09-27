<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ConsentRepository;
use App\Repositories\PrivacyPolicyRepository;
use RuntimeException;

class ConsentService
{
	private PrivacyPolicyRepository $privacyPolicyRepository;
	private ConsentRepository $consentRepository;

	public function __construct()
	{
		$this->privacyPolicyRepository = new PrivacyPolicyRepository();
		$this->consentRepository = new ConsentRepository();
	}

	public function storeRegistrationConsents(
		int $userId,
		bool $marketingOptIn,
		bool $ageDeclarationAccepted,
		?string $ipAddress = null,
		?string $userAgent = null
	): void
	{
		$privacyVersion = $this->privacyPolicyRepository->getLatestActiveVersion()
			?? $this->privacyPolicyRepository->getLatestVersion();

		if ($privacyVersion === null) {
			throw new RuntimeException('Nessuna versione privacy disponibile.');
		}

		$this->consentRepository->recordPrivacyAcceptance([
			'privacy_policy_version_id' => (int) $privacyVersion['id'],
			'user_id' => $userId,
			'anonymous_token' => null,
			'ip_address' => $ipAddress,
			'user_agent' => $userAgent,
		]);

		$this->consentRepository->upsertMarketingConsent($userId, $marketingOptIn);
		$this->consentRepository->recordAgeDeclaration($userId, $ageDeclarationAccepted);
	}

	public function updateMarketingConsent(int $userId, bool $marketingOptIn): void
	{
		$this->consentRepository->upsertMarketingConsent($userId, $marketingOptIn);
	}

	public function recordAgeDeclaration(int $userId, bool $declaredAdult): void
	{
		$this->consentRepository->recordAgeDeclaration($userId, $declaredAdult);
	}
}
