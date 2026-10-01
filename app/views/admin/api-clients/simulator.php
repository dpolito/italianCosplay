<?php
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$old = static fn (string $key, string $default = ''): string => (string) ($_POST[$key] ?? $_GET[$key] ?? $default);
$selectedRegion = $old('regione', $old('region_id'));
$selectedProvince = $old('provincia', $old('province_id'));
$selectedComune = $old('comune');
?>
<div class="space-y-5">
	<div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
		<div>
			<a href="/admin/api-clients" class="text-sm font-semibold text-green-700 hover:underline">Torna ad API Management</a>
			<h1 class="mt-1 text-2xl font-bold text-gray-900">Simulatore API</h1>
		</div>
		<a href="/admin/api-clients/logs" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">Log API</a>
	</div>

	<form method="post" action="/admin/api-clients/simulator" class="rounded-md border border-gray-200 bg-white p-4" data-api-simulator-form>
		<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
		<input type="hidden" name="region_id" data-region-id value="<?= $h($old('region_id', $selectedRegion)) ?>">
		<input type="hidden" name="province_id" data-province-id value="<?= $h($old('province_id', $selectedProvince)) ?>">

		<div class="grid gap-3 lg:grid-cols-2">
			<div>
				<label class="text-sm font-semibold text-gray-700">API client</label>
				<select name="client_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
					<option value="">Seleziona client</option>
					<?php foreach ($clients as $client): ?>
						<option value="<?= (int) $client['id'] ?>" <?= (int) $selectedClientId === (int) $client['id'] ? 'selected' : '' ?>>
							<?= $h($client['name']) ?> · <?= $h($client['environment']) ?> · <?= $h($client['status']) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label class="text-sm font-semibold text-gray-700">Endpoint</label>
				<select name="endpoint" data-endpoint-select class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
					<?php foreach ($endpoints as $key => $endpoint): ?>
						<option value="<?= $h($key) ?>" <?= $selectedEndpoint === $key ? 'selected' : '' ?>>
							<?= $h($endpoint['label']) ?> · <?= $h($endpoint['scope']) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(220px,1fr)_160px_160px_96px_96px]" data-panel="events_search">
			<div>
				<label class="text-sm font-semibold text-gray-700">Cerca</label>
				<input name="q" value="<?= $h($old('q')) ?>" placeholder="romics" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
			</div>
			<div>
				<label class="text-sm font-semibold text-gray-700">Dal</label>
				<input type="date" name="from" value="<?= $h($old('from')) ?>" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
			</div>
			<div>
				<label class="text-sm font-semibold text-gray-700">Al</label>
				<input type="date" name="to" value="<?= $h($old('to')) ?>" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
			</div>
			<div>
				<label class="text-sm font-semibold text-gray-700">Limit</label>
				<input type="number" min="1" name="limit" value="<?= $h($old('limit', '10')) ?>" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
			</div>
			<div>
				<label class="text-sm font-semibold text-gray-700">Page</label>
				<input type="number" min="1" name="page" value="<?= $h($old('page', '1')) ?>" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
			</div>
		</div>

		<div class="mt-4 grid gap-3 md:grid-cols-3" data-location-panel>
			<div>
				<label class="text-sm font-semibold text-gray-700">Regione</label>
				<select name="regione" data-region-select class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
					<option value="">Tutte le regioni</option>
					<?php foreach ($regions as $region): ?>
						<option value="<?= (int) $region['id'] ?>" <?= (string) $selectedRegion === (string) $region['id'] ? 'selected' : '' ?>><?= $h($region['nome']) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label class="text-sm font-semibold text-gray-700">Provincia</label>
				<select name="provincia" data-province-select class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
					<option value="">Tutte le province</option>
				</select>
			</div>
			<div>
				<label class="text-sm font-semibold text-gray-700">Comune</label>
				<select name="comune" data-municipality-select class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
					<option value="">Tutti i comuni</option>
				</select>
			</div>
		</div>

		<div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(280px,1fr)_96px_96px]" data-panel="event_detail event_photos">
			<div>
				<label class="text-sm font-semibold text-gray-700">Slug evento</label>
				<input name="slug" value="<?= $h($old('slug')) ?>" placeholder="romics-2026" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
			</div>
			<div data-photo-only>
				<label class="text-sm font-semibold text-gray-700">Limit</label>
				<input type="number" min="1" name="photos_limit" value="<?= $h($old('photos_limit', $old('limit', '12'))) ?>" data-limit-proxy class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
			</div>
			<div data-photo-only>
				<label class="text-sm font-semibold text-gray-700">Page</label>
				<input type="number" min="1" name="photos_page" value="<?= $h($old('photos_page', $old('page', '1'))) ?>" data-page-proxy class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
			</div>
		</div>

		<div class="mt-4 grid gap-3 md:grid-cols-3" data-panel="locations">
			<div>
				<label class="text-sm font-semibold text-gray-700">Tipo location</label>
				<select name="location_type" data-location-type class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
					<?php foreach (['regioni' => 'Regioni', 'province' => 'Province per regione', 'comuni' => 'Comuni per provincia'] as $type => $label): ?>
						<option value="<?= $h($type) ?>" <?= $old('location_type', 'regioni') === $type ? 'selected' : '' ?>><?= $h($label) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="md:col-span-2 flex items-end">
				<p class="text-sm text-gray-600">Per province seleziona una regione; per comuni seleziona una provincia.</p>
			</div>
		</div>

		<div class="mt-5 flex flex-wrap items-center gap-3">
			<button class="rounded-md bg-blue-700 px-5 py-2 font-semibold text-white hover:bg-blue-800" data-submit-label>Simula richiesta</button>
			<p class="text-sm text-gray-500">Non consuma rate limit e non scrive nei log API.</p>
		</div>
	</form>

	<div data-simulator-result <?= $result === null ? 'hidden' : '' ?>>
		<div class="grid gap-5 xl:grid-cols-[300px_minmax(0,1fr)]">
			<div class="space-y-4 rounded-md border border-gray-200 bg-white p-5">
				<h2 class="text-lg font-bold text-gray-900">Esito</h2>
				<div>
					<p class="text-sm font-semibold text-gray-500">HTTP status</p>
					<p class="mt-1 text-2xl font-bold <?= (int) ($result['status'] ?? 500) >= 400 ? 'text-red-700' : 'text-green-700' ?>" data-result-status><?= $result !== null ? (int) ($result['status'] ?? 500) : '' ?></p>
				</div>
				<div data-result-client <?= empty($result['client']) ? 'hidden' : '' ?>>
					<p class="text-sm font-semibold text-gray-500">Client simulato</p>
					<p class="mt-1 font-semibold text-gray-900" data-result-client-name><?= $h($result['client']['name'] ?? '') ?></p>
					<p class="text-xs text-gray-500" data-result-client-meta><?= $h(($result['client']['environment'] ?? '') . (!empty($result['client']) ? ' · ' : '') . ($result['client']['status'] ?? '') . (!empty($result['client']) ? ' · ' : '') . ($result['client']['key_prefix'] ?? '')) ?></p>
				</div>
				<div data-result-endpoint <?= empty($result['endpoint']) ? 'hidden' : '' ?>>
					<p class="text-sm font-semibold text-gray-500">Scope richiesto</p>
					<p class="mt-1 font-mono text-sm text-gray-900" data-result-scope><?= $h($result['endpoint']['scope'] ?? '') ?></p>
				</div>
			</div>
			<div class="rounded-md border border-gray-200 bg-gray-950 p-5">
				<div class="mb-3 flex items-center justify-between">
					<h2 class="text-sm font-bold uppercase text-gray-300">JSON response</h2>
					<button type="button" data-copy-json class="rounded-md bg-gray-800 px-3 py-1 text-xs font-semibold text-white">Copia</button>
				</div>
				<pre id="simulator-json" class="max-h-[640px] overflow-auto whitespace-pre-wrap text-sm leading-6 text-green-100"><?= $h($result['json'] ?? '') ?></pre>
			</div>
		</div>
		<div class="mt-5 rounded-md border border-gray-200 bg-white p-5" data-code-examples>
			<div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
				<div>
					<h2 class="text-lg font-bold text-gray-900">Esempi richiesta</h2>
					<p class="mt-1 text-sm text-gray-500">Usa <code class="rounded bg-gray-100 px-1">&lt;API_KEY&gt;</code> come placeholder della chiave reale.</p>
				</div>
				<button type="button" data-copy-code class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">Copia codice</button>
			</div>
			<div class="mt-4 flex flex-wrap gap-2" role="tablist" aria-label="Linguaggi esempi API">
				<?php foreach (['curl' => 'cURL', 'php' => 'PHP', 'javascript' => 'JavaScript', 'csharp' => 'C#', 'python' => 'Python'] as $key => $label): ?>
					<button type="button" data-code-tab="<?= $h($key) ?>" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"><?= $h($label) ?></button>
				<?php endforeach; ?>
			</div>
			<pre class="mt-3 max-h-[420px] overflow-auto rounded-md bg-gray-950 p-4 text-sm leading-6 text-white"><code data-code-output></code></pre>
		</div>
	</div>
</div>

<script>
	const apiSimulatorData = {
		provinces: <?= json_encode($provinces, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
		municipalities: <?= json_encode($municipalities, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
		selectedProvince: <?= json_encode((string) $selectedProvince) ?>,
		selectedMunicipality: <?= json_encode((string) $selectedComune) ?>
	};

	const endpointSelect = document.querySelector('[data-endpoint-select]');
	const regionSelect = document.querySelector('[data-region-select]');
	const provinceSelect = document.querySelector('[data-province-select]');
	const municipalitySelect = document.querySelector('[data-municipality-select]');
	const regionId = document.querySelector('[data-region-id]');
	const provinceId = document.querySelector('[data-province-id]');
	const limitProxy = document.querySelector('[data-limit-proxy]');
	const pageProxy = document.querySelector('[data-page-proxy]');
	const limitInput = document.querySelector('input[name="limit"]');
	const pageInput = document.querySelector('input[name="page"]');
	const simulatorForm = document.querySelector('[data-api-simulator-form]');
	const submitLabel = document.querySelector('[data-submit-label]');
	const resultWrapper = document.querySelector('[data-simulator-result]');
	const statusNode = document.querySelector('[data-result-status]');
	const clientBox = document.querySelector('[data-result-client]');
	const clientName = document.querySelector('[data-result-client-name]');
	const clientMeta = document.querySelector('[data-result-client-meta]');
	const endpointBox = document.querySelector('[data-result-endpoint]');
	const scopeNode = document.querySelector('[data-result-scope]');
	const jsonNode = document.getElementById('simulator-json');
	const codeOutput = document.querySelector('[data-code-output]');
	const codeTabs = document.querySelectorAll('[data-code-tab]');
	let activeCodeLanguage = 'curl';

	function option(label, value) {
		const node = document.createElement('option');
		node.value = value;
		node.textContent = label;
		return node;
	}

	function fillProvinces() {
		const region = regionSelect.value;
		provinceSelect.innerHTML = '';
		provinceSelect.appendChild(option('Tutte le province', ''));
		apiSimulatorData.provinces
			.filter((province) => !region || String(province.regione_id) === String(region))
			.forEach((province) => provinceSelect.appendChild(option(province.nome, province.id)));
		provinceSelect.value = apiSimulatorData.selectedProvince || '';
		if (provinceSelect.value !== apiSimulatorData.selectedProvince) provinceSelect.value = '';
		regionId.value = region;
		fillMunicipalities();
	}

	function fillMunicipalities() {
		const province = provinceSelect.value;
		municipalitySelect.innerHTML = '';
		municipalitySelect.appendChild(option('Tutti i comuni', ''));
		apiSimulatorData.municipalities
			.filter((municipality) => !province || String(municipality.provincia_id) === String(province))
			.forEach((municipality) => municipalitySelect.appendChild(option(municipality.nome, municipality.id)));
		municipalitySelect.value = apiSimulatorData.selectedMunicipality || '';
		if (municipalitySelect.value !== apiSimulatorData.selectedMunicipality) municipalitySelect.value = '';
		provinceId.value = province;
	}

	function syncPanels() {
		const endpoint = endpointSelect.value;
		document.querySelectorAll('[data-panel]').forEach((panel) => {
			panel.hidden = !panel.dataset.panel.split(' ').includes(endpoint);
		});
		document.querySelector('[data-location-panel]').hidden = endpoint === 'event_detail' || endpoint === 'event_photos';
		document.querySelectorAll('[data-photo-only]').forEach((node) => node.hidden = endpoint !== 'event_photos');
		if (endpoint === 'event_photos') {
			limitInput.value = limitProxy.value || '12';
			pageInput.value = pageProxy.value || '1';
		}
	}

	function requestInfo() {
		const endpoint = endpointSelect.value;
		const origin = window.location.origin || 'https://www.italiancosplay.it';
		let path = '/api/v1/events/search';
		const params = new URLSearchParams();
		if (endpoint === 'events_search') {
			[
				['q', simulatorForm.elements.q.value],
				['from', simulatorForm.elements.from.value],
				['to', simulatorForm.elements.to.value],
				['regione', regionSelect.value],
				['provincia', provinceSelect.value],
				['comune', municipalitySelect.value],
				['limit', limitInput.value],
				['page', pageInput.value]
			].forEach(([key, value]) => {
				if (value) params.set(key, value);
			});
		} else if (endpoint === 'event_detail') {
			path = '/api/v1/events/' + encodeURIComponent(simulatorForm.elements.slug.value || '{slug}');
		} else if (endpoint === 'event_photos') {
			path = '/api/v1/events/' + encodeURIComponent(simulatorForm.elements.slug.value || '{slug}') + '/photos';
			if (limitProxy.value) params.set('limit', limitProxy.value);
			if (pageProxy.value) params.set('page', pageProxy.value);
		} else if (endpoint === 'locations') {
			const type = simulatorForm.elements.location_type.value || 'regioni';
			path = '/api/v1/locations/' + encodeURIComponent(type);
			if (type === 'province' && regionSelect.value) params.set('region_id', regionSelect.value);
			if (type === 'comuni' && provinceSelect.value) params.set('province_id', provinceSelect.value);
		}
		const query = params.toString();
		return {
			url: origin + path + (query ? '?' + query : ''),
			apiKey: '<API_KEY>'
		};
	}

	function codeExample(language) {
		const request = requestInfo();
		const url = request.url;
		const apiKey = request.apiKey;
		const escapedUrl = url.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
		if (language === 'php') {
			return `<` + `?php
$ch = curl_init("${escapedUrl}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer ${apiKey}",
        "Accept: application/json",
    ],
]);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo $statusCode . PHP_EOL;
echo $response;`;
		}
		if (language === 'javascript') {
			return `const response = await fetch("${escapedUrl}", {
  method: "GET",
  headers: {
    "Authorization": "Bearer ${apiKey}",
    "Accept": "application/json"
  }
});

const data = await response.json();
console.log(response.status, data);`;
		}
		if (language === 'csharp') {
			return `using System.Net.Http.Headers;

using var client = new HttpClient();
client.DefaultRequestHeaders.Authorization =
    new AuthenticationHeaderValue("Bearer", "${apiKey}");
client.DefaultRequestHeaders.Accept.Add(
    new MediaTypeWithQualityHeaderValue("application/json"));

var response = await client.GetAsync("${escapedUrl}");
var body = await response.Content.ReadAsStringAsync();

Console.WriteLine((int)response.StatusCode);
Console.WriteLine(body);`;
		}
		if (language === 'python') {
			return `import requests

response = requests.get(
    "${escapedUrl}",
    headers={
        "Authorization": "Bearer ${apiKey}",
        "Accept": "application/json",
    },
    timeout=20,
)

print(response.status_code)
print(response.json())`;
		}
		return `curl --request GET \\
  --url "${url}" \\
  --header "Authorization: Bearer ${apiKey}" \\
  --header "Accept: application/json"`;
	}

	function updateCodeExample() {
		if (!codeOutput) return;
		codeOutput.textContent = codeExample(activeCodeLanguage);
		codeTabs.forEach((tab) => {
			const active = tab.dataset.codeTab === activeCodeLanguage;
			tab.className = active
				? 'rounded-md border border-blue-700 bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white'
				: 'rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50';
		});
	}

	endpointSelect?.addEventListener('change', syncPanels);
	regionSelect?.addEventListener('change', () => {
		apiSimulatorData.selectedProvince = '';
		apiSimulatorData.selectedMunicipality = '';
		fillProvinces();
	});
	provinceSelect?.addEventListener('change', () => {
		apiSimulatorData.selectedMunicipality = '';
		fillMunicipalities();
	});
	limitProxy?.addEventListener('input', () => limitInput.value = limitProxy.value);
	pageProxy?.addEventListener('input', () => pageInput.value = pageProxy.value);
	document.querySelector('[data-copy-json]')?.addEventListener('click', async function () {
		await navigator.clipboard.writeText(jsonNode.textContent);
		this.textContent = 'Copiato';
	});
	codeTabs.forEach((tab) => {
		tab.addEventListener('click', () => {
			activeCodeLanguage = tab.dataset.codeTab;
			updateCodeExample();
		});
	});
	document.querySelector('[data-copy-code]')?.addEventListener('click', async function () {
		await navigator.clipboard.writeText(codeOutput.textContent);
		this.textContent = 'Copiato';
	});
	simulatorForm?.addEventListener('input', updateCodeExample);
	simulatorForm?.addEventListener('change', updateCodeExample);
	simulatorForm?.addEventListener('submit', async function (event) {
		event.preventDefault();
		submitLabel.disabled = true;
		submitLabel.textContent = 'Simulazione...';
		if (endpointSelect.value === 'event_photos') {
			limitInput.value = limitProxy.value || '12';
			pageInput.value = pageProxy.value || '1';
		}
		try {
			const response = await fetch(simulatorForm.action, {
				method: 'POST',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json'
				},
				body: new FormData(simulatorForm)
			});
			const payload = await response.json();
			const result = payload.result || {};
			const status = Number(result.status || 500);
			resultWrapper.hidden = false;
			statusNode.textContent = String(status);
			statusNode.className = 'mt-1 text-2xl font-bold ' + (status >= 400 ? 'text-red-700' : 'text-green-700');
			if (result.client) {
				clientBox.hidden = false;
				clientName.textContent = result.client.name || '';
				clientMeta.textContent = [result.client.environment, result.client.status, result.client.key_prefix].filter(Boolean).join(' · ');
			} else {
				clientBox.hidden = true;
			}
			if (result.endpoint) {
				endpointBox.hidden = false;
				scopeNode.textContent = result.endpoint.scope || '';
			} else {
				endpointBox.hidden = true;
			}
			jsonNode.textContent = result.json || JSON.stringify(result.body || {}, null, 2);
			updateCodeExample();
		} catch (error) {
			resultWrapper.hidden = false;
			statusNode.textContent = '500';
			statusNode.className = 'mt-1 text-2xl font-bold text-red-700';
			clientBox.hidden = true;
			endpointBox.hidden = true;
			jsonNode.textContent = JSON.stringify({ success: false, error: { code: 'SIMULATOR_REQUEST_FAILED', message: 'Errore durante la simulazione.' } }, null, 2);
			updateCodeExample();
		} finally {
			submitLabel.disabled = false;
			submitLabel.textContent = 'Simula richiesta';
		}
	});
	fillProvinces();
	syncPanels();
	updateCodeExample();
</script>
