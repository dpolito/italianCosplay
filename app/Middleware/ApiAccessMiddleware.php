<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Services\ApiAccessService;

final class ApiAccessMiddleware extends Middleware
{
	public function __construct(private string $scope)
	{
	}

	public function handle(): void
	{
		(new ApiAccessService())->authorize($this->scope);
	}
}
