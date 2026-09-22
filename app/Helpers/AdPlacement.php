<?php

namespace App\Helpers;

use App\Services\AdRotationService;

class AdPlacement
{
	public static function render(string $positionCode, string $page, string $wrapperClass = '', string $imageClass = '', array $context = []): string
	{
		$banner = (new AdRotationService())->getBannerForPosition($positionCode, $page, $context);

		if (empty($banner)) {
			return '';
		}

		$wrapperClass = $wrapperClass !== '' ? $wrapperClass : 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm';
		$imageClass = $imageClass !== '' ? $imageClass : 'block h-auto w-full max-w-full object-contain';
		$title = htmlspecialchars((string) ($banner['title'] ?? 'Banner sponsorizzato'), ENT_QUOTES, 'UTF-8');
		$description = trim((string) ($banner['description'] ?? ''));
		$sponsorName = trim((string) ($banner['sponsor_name'] ?? ''));
		$altText = htmlspecialchars((string) ($banner['alt_text'] ?? $banner['title'] ?? 'Banner sponsorizzato'), ENT_QUOTES, 'UTF-8');
		$positionClass = str_contains($positionCode, 'sidebar')
			? 'compact'
			: (str_starts_with($positionCode, 'homepage') ? 'featured' : 'contextual');
		$mobileImage = !empty($banner['mobile_image']) ? sprintf(
			'<source media="(max-width: 767px)" srcset="%s">',
			htmlspecialchars((string)$banner['mobile_image'], ENT_QUOTES, 'UTF-8')
		) : '';
		$picture = sprintf(
			'<picture>%s<img src="%s" alt="%s" class="%s" loading="lazy" decoding="async"></picture>',
			$mobileImage,
			htmlspecialchars((string) ($banner['image'] ?? ''), ENT_QUOTES, 'UTF-8'),
			$altText,
			htmlspecialchars($imageClass, ENT_QUOTES, 'UTF-8')
		);
		$contextLabel = $sponsorName !== '' ? 'Sponsorizzato da ' . htmlspecialchars($sponsorName, ENT_QUOTES, 'UTF-8') : 'Contenuto sponsorizzato';
		$descriptionMarkup = $description !== ''
			? '<p class="mt-2 line-clamp-2 text-sm leading-relaxed text-slate-600">' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</p>'
			: '';
		$logoMarkup = !empty($banner['logo'])
			? '<img src="' . htmlspecialchars((string)$banner['logo'], ENT_QUOTES, 'UTF-8') . '" alt="" class="h-9 max-w-28 object-contain object-left">'
			: '';
		$ctaMarkup = '<span class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-green-800">Scopri di più <span aria-hidden="true">&rarr;</span></span>';

		$content = match ($positionClass) {
			'compact' => sprintf(
				'<div class="bg-white">%s<div class="p-4"><p class="text-[11px] font-bold uppercase tracking-[0.16em] text-green-800">%s</p>%s<h3 class="mt-2 text-lg font-bold leading-tight text-slate-950">%s</h3>%s%s</div></div>',
				$picture,
				$contextLabel,
				$logoMarkup,
				$title,
				$descriptionMarkup,
				$ctaMarkup
			),
			'featured' => sprintf(
				'<div class="grid items-stretch bg-white md:grid-cols-[minmax(0,1.45fr)_minmax(260px,0.8fr)]"><div class="flex items-center bg-slate-50">%s</div><div class="flex flex-col justify-center p-5 md:p-7"><p class="text-[11px] font-bold uppercase tracking-[0.16em] text-green-800">%s</p>%s<h2 class="mt-2 text-xl font-bold leading-tight text-slate-950 md:text-2xl">%s</h2>%s%s</div></div>',
				$picture,
				$contextLabel,
				$logoMarkup,
				$title,
				$descriptionMarkup,
				$ctaMarkup
			),
			default => sprintf(
				'<div class="grid items-center gap-4 bg-white p-4 md:grid-cols-[minmax(0,1.5fr)_minmax(230px,0.8fr)] md:p-5"><div class="overflow-hidden rounded-xl bg-slate-50">%s</div><div><p class="text-[11px] font-bold uppercase tracking-[0.16em] text-green-800">%s</p>%s<h3 class="mt-2 text-lg font-bold leading-tight text-slate-950">%s</h3>%s%s</div></div>',
				$picture,
				$contextLabel,
				$logoMarkup,
				$title,
				$descriptionMarkup,
				$ctaMarkup
			)
		};

		return sprintf(
			'<a href="%s" class="group block" rel="sponsored noopener noreferrer" aria-label="%s"><article class="%s transition-shadow duration-200 group-hover:shadow-lg">%s</article></a>',
			htmlspecialchars((string) ($banner['target_url'] ?? '#'), ENT_QUOTES, 'UTF-8'),
			$title,
			htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8'),
			$content
		);
	}

}
