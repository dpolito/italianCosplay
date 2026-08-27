/**
 * Admin Detail Renderer - Event
 */


window.adminDetailRenderers =
	window.adminDetailRenderers || {};



window.adminDetailRenderers.event =
	function(event)
	{

		const content =
			document.querySelector(
				'[data-admin-detail-content]'
			);


		if(!content){

			return;

		}



		content.innerHTML = `

			<div class="space-y-5">


				<div>

					<h2 class="text-xl font-bold text-gray-800">

						${escapeHtml(event.titolo ?? '')}

					</h2>


					${
			event.slug

				?

				`
						<div class="text-sm text-gray-500 mt-1">

							${escapeHtml(event.slug)}

						</div>
						`

				:

				''
		}

				</div>



				<div class="grid grid-cols-2 gap-4">


					<div>

						<div class="text-xs text-gray-500">
							Data inizio
						</div>


						<div class="font-medium">

							${event.data_inizio ?? '-'}

						</div>

					</div>



					<div>

						<div class="text-xs text-gray-500">
							Data fine
						</div>


						<div class="font-medium">

							${event.data_fine ?? '-'}

						</div>

					</div>


				</div>



				<div>

					<div class="text-xs text-gray-500">
						Luogo
					</div>


					<div class="font-medium">

						${escapeHtml(event.luogo ?? '-')}

					</div>

				</div>



				<div>

					<div class="text-xs text-gray-500">
						Stato
					</div>


					<div class="mt-1">

						${
			event.approvato == 1

				?

				`
							<span
								class="
								inline-flex
								rounded-full
								bg-green-100
								px-2
								py-1
								text-xs
								font-medium
								text-green-800
								">

								Pubblicato

							</span>
							`

				:

				`
							<span
								class="
								inline-flex
								rounded-full
								bg-yellow-100
								px-2
								py-1
								text-xs
								font-medium
								text-yellow-800
								">

								In attesa

							</span>
							`
		}

					</div>

				</div>



				<div class="pt-4 border-t flex gap-3">


					<a
						href="/admin/events/edit/${event.id}"
						class="
						inline-flex
						items-center
						rounded-md
						bg-blue-600
						px-4
						py-2
						text-white
						hover:bg-blue-700
						">

						Modifica evento

					</a>



					<button
						type="button"
						data-admin-event-delete
						data-id="${event.id}"
						class="
						inline-flex
						items-center
						rounded-md
						bg-red-600
						px-4
						py-2
						text-white
						hover:bg-red-700
						">

						Cancella evento

					</button>


				</div>


			</div>

		`;



		bindDeleteEvent();


	};



/**
 * Associazione delete dopo rendering
 */
function bindDeleteEvent()
{

	const button =
		document.querySelector(
			'[data-admin-event-delete]'
		);



	if(!button){

		return;

	}



	button.onclick =
		async ()=>{


			const id =
				button.dataset.id;



			if(
				!confirm(
					'Sei sicuro di voler cancellare questo evento?'
				)
			){

				return;

			}



			try {


				const csrf =
					document.querySelector(
						'input[name="csrf_token"]'
					)?.value;



				const response =
					await fetch(
						`/admin/events/delete/${id}`,
						{

							method:'POST',

							headers:{

								'X-Requested-With':
									'XMLHttpRequest'

							},

							body:
								new URLSearchParams({

									csrf_token: csrf

								})

						}
					);



				const result =
					await response.json();



				if(!result.success){

					throw new Error(
						result.message ??
						'Errore cancellazione evento'
					);

				}



				/**
				 * chiude pannello
				 */
				if(
					typeof closePanel === 'function'
				){

					closePanel();

				}



				/**
				 * ricarica lista
				 */
				document.dispatchEvent(
					new CustomEvent(
						'admin-list:reload'
					)
				);


			}
			catch(error){


				console.error(
					'Delete event error:',
					error
				);


				alert(
					error.message
				);


			}


		};


}



/**
 * Escape HTML
 */
function escapeHtml(value)
{

	return String(value)
		.replace(
			/[&<>"']/g,
			char => ({

				'&':'&amp;',
				'<':'&lt;',
				'>':'&gt;',
				'"':'&quot;',
				"'":'&#039;'

			})[char]
		);

}



console.log(
	'EVENT RENDERER CARICATO'
);
