/**
 * Admin Detail Panel
 *
 * Gestione pannello dettaglio laterale.
 *
 * Features:
 * - AJAX loading
 * - Renderer dinamici
 * - Open / Close panel
 * - Layout resize
 */


function openPanel()
{

	const panel =
		document.querySelector(
			'[data-admin-detail-panel]'
		);


	const wrapper =
		document.querySelector(
			'[data-admin-table-wrapper]'
		);



	if(!panel){

		return;

	}



	panel.classList.remove(
		'hidden'
	);



	if(wrapper){

		wrapper.classList.remove(
			'flex-1'
		);


		wrapper.classList.add(
			'w-2/3'
		);

	}


}



function closePanel()
{

	const panel =
		document.querySelector(
			'[data-admin-detail-panel]'
		);


	const wrapper =
		document.querySelector(
			'[data-admin-table-wrapper]'
		);



	if(panel){

		panel.classList.add(
			'hidden'
		);

	}



	if(wrapper){

		wrapper.classList.remove(
			'w-2/3'
		);


		wrapper.classList.add(
			'flex-1'
		);

	}


}



function showDetailLoading()
{

	const content =
		document.querySelector(
			'[data-admin-detail-content]'
		);



	if(content){

		content.innerHTML = `

			<div class="py-8 text-center text-gray-500">

				Caricamento...

			</div>

		`;

	}


}



function showDetailError(message)
{

	const content =
		document.querySelector(
			'[data-admin-detail-content]'
		);



	if(content){

		content.innerHTML = `

			<div class="py-8 text-center text-red-600">

				${message}

			</div>

		`;

	}


}



async function loadDetail(config)
{

	try {

		if(
			!config ||
			!config.endpoint ||
			!config.id
		){
			throw new Error(
				'Configurazione dettaglio non valida'
			);
		}


		showDetailLoading();


		const url =
			config.endpoint.replace(
				'{id}',
				config.id
			);



		const response =
			await fetch(
				url,
				{
					headers:{
						'Accept':'application/json'
					}
				}
			);



		const json =
			await response.json();



		if(!json.success){

			throw new Error(
				json.message ??
				'Errore caricamento dettaglio'
			);

		}



		const renderer =
			window.adminDetailRenderers?.[
				config.renderer
				];



		if(!renderer){

			throw new Error(
				'Renderer non trovato: ' + config.renderer
			);

		}



		openPanel();



		renderer(
			json.data
		);



	}
	catch(error){


		console.error(
			'Admin Detail error:',
			error
		);


		showDetailError(
			error.message
		);


	}

}





document.addEventListener(
	'DOMContentLoaded',
	()=>{


		const closeButton =
			document.querySelector(
				'[data-admin-detail-close]'
			);



		closeButton?.addEventListener(
			'click',
			()=>{

				closePanel();

			}
		);


	}
);





/**
 * Evento apertura dettaglio
 *
 * Esempio:
 *
 * document.dispatchEvent(
 *   new CustomEvent(
 *     'admin-detail:open',
 *     {
 *       detail:{
 *          id:554,
 *          endpoint:'/admin/events/detail/{id}',
 *          renderer:'event'
 *       }
 *     }
 *   )
 * );
 */


document.addEventListener(
	'admin-detail:open',
	event=>{


		loadDetail(
			event.detail
		);


	}
);
