class AdminList {

	constructor(element) {

		this.element = element;

		this.config = JSON.parse(
			this.element.dataset.config || '{}'
		);

		this.page = 1;

		this.perPage =
			this.config.pageSize ?? 25;

		this.sort =
			this.config.defaultSort ?? null;

		this.direction =
			this.config.defaultDirection ?? 'desc';

		this.search = '';

		this.filters = this.config.initialFilters ?? {};

		this.formatters = {};

		this.init();

	}




	init() {

		this.cacheElements();

		this.registerFormatters();

		this.applyInitialState();

		this.bindEvents();

		this.load();

	}

	applyInitialState() {

		if(this.config.initialSearch && this.searchInput){

			this.search =
				String(this.config.initialSearch);

			this.searchInput.value =
				this.search;

		}

		this.element
			.querySelectorAll(
				'[data-admin-list-filter]'
			)
			.forEach(filter=>{

				const name =
					filter.dataset.adminListFilter;

				if(
					name &&
					this.filters[name] !== undefined
				){

					filter.value =
						this.filters[name];

				}

			});

	}



	cacheElements() {

		this.body =
			this.element.querySelector(
				'[data-admin-list-body]'
			);

		this.loading =
			this.element.querySelector(
				'[data-admin-list-loading-state]'
			);

		this.empty =
			this.element.querySelector(
				'[data-admin-list-empty-state]'
			);

		this.noResults =
			this.element.querySelector(
				'[data-admin-list-no-results]'
			);

		this.total =
			this.element.querySelector(
				'[data-admin-list-total]'
			);

		this.pageNumbers =
			this.element.querySelector(
				'[data-admin-list-page-numbers]'
			);

		this.prevButton =
			this.element.querySelector(
				'[data-admin-list-page-prev]'
			);

		this.nextButton =
			this.element.querySelector(
				'[data-admin-list-page-next]'
			);

		this.pageSize =
			this.element.querySelector(
				'[data-admin-list-page-size]'
			);

		this.searchInput =
			this.element.querySelector(
				'[data-admin-list-search]'
			);

	}



	registerFormatters() {


		this.formatters.eventStatus =
			value => {


				const statuses = {

					1: {
						label:'Pubblicato',
						className:
							'bg-green-100 text-green-800'
					},

					0: {
						label:'In attesa',
						className:
							'bg-yellow-100 text-yellow-800'
					}

				};


				return statuses[value] ?? {

					label:value ?? '',

					className:
						'bg-gray-100 text-gray-800'

				};


			};


		this.formatters.userVerified =
			value => {


				return String(value) === '1'
					? {
						label: 'Sì',
						className: 'bg-green-100 text-green-800'
					}
					: {
						label: 'No',
						className: 'bg-yellow-100 text-yellow-800'
					};


			};


		this.formatters.userPrivacyConsent =
			value => {


				return value
					? {
						label: 'Accettata',
						className: 'bg-green-100 text-green-800'
					}
					: {
						label: 'Manca',
						className: 'bg-red-100 text-red-800'
					};


			};


		this.formatters.userMarketingConsent =
			value => {


				return value
					? {
						label: 'Sì',
						className: 'bg-amber-100 text-amber-800'
					}
					: {
						label: 'No',
						className: 'bg-gray-100 text-gray-800'
					};


			};


		this.formatters.userAgeDeclaration =
			value => {


				return String(value) === '1'
					? {
						label: 'Sì',
						className: 'bg-green-100 text-green-800'
					}
					: {
						label: 'No',
						className: 'bg-red-100 text-red-800'
					};


			};


		this.formatters.paymentLogSuccess =
			value => {


				return String(value) === '1'
					? {
						label: 'OK',
						className: 'bg-green-100 text-green-800'
					}
					: {
						label: 'Errore',
						className: 'bg-red-100 text-red-800'
					};


			};

		this.formatters.emailEvent =
			value => {

				const normalized =
					String(value ?? '')
						.replace(/([a-z])([A-Z])/g, '$1_$2')
						.toLowerCase();

				const statuses = {
					request: { label: 'Inviata', className: 'bg-blue-100 text-blue-800' },
					delivered: { label: 'Consegnata', className: 'bg-green-100 text-green-800' },
					opened: { label: 'Aperta', className: 'bg-emerald-100 text-emerald-800' },
					unique_opened: { label: 'Prima apertura', className: 'bg-emerald-100 text-emerald-800' },
					click: { label: 'Click', className: 'bg-indigo-100 text-indigo-800' },
					clicked: { label: 'Click', className: 'bg-indigo-100 text-indigo-800' },
					soft_bounce: { label: 'Soft bounce', className: 'bg-amber-100 text-amber-800' },
					hard_bounce: { label: 'Hard bounce', className: 'bg-red-100 text-red-800' },
					blocked: { label: 'Bloccata', className: 'bg-red-100 text-red-800' },
					spam: { label: 'Spam', className: 'bg-red-100 text-red-800' },
					invalid: { label: 'Non valida', className: 'bg-red-100 text-red-800' },
					deferred: { label: 'Rimandata', className: 'bg-yellow-100 text-yellow-800' },
					unsubscribed: { label: 'Disiscritta', className: 'bg-gray-100 text-gray-800' }
				};

				return statuses[normalized] ?? {
					label: value ?? 'N/D',
					className: 'bg-gray-100 text-gray-800'
				};

			};

		this.formatters.emailTag =
			value => {

				return {
					label: value || 'senza_tag',
					className: value
						? 'bg-slate-100 text-slate-800'
						: 'bg-gray-100 text-gray-700'
				};

			};

		this.formatters.legacyInvitationStatus =
			value => {

				const statuses = {
					pending: {
						label: 'Da inviare',
						className: 'bg-gray-100 text-gray-800'
					},
					sent: {
						label: 'Inviata',
						className: 'bg-green-100 text-green-800'
					},
					failed: {
						label: 'Fallita',
						className: 'bg-red-100 text-red-800'
					}
				};

				return statuses[value] ?? {
					label: value ?? '',
					className: 'bg-gray-100 text-gray-800'
				};

			};


	}

	showToast(message, type='success'){

		const toast =
			document.getElementById(
				'admin-toast'
			);

		if(!toast){
			return;
		}

		toast.className =
			`admin-toast ${type}`;

		toast.textContent =
			message;

		requestAnimationFrame(
			()=>{
				toast.classList.add(
					'visible'
				);
			}
		);

		clearTimeout(
			this.toastTimer
		);

		this.toastTimer =
			setTimeout(
				()=>{
					toast.classList.remove(
						'visible'
					);
				},
				2400
			);

	}



	bindEvents() {


		if(this.searchInput){

			let timer;


			this.searchInput.addEventListener(
				'input',
				()=>{

					clearTimeout(timer);


					timer=setTimeout(
						()=>{

							this.search =
								this.searchInput.value.trim();


							this.page=1;

							this.load();

						},
						300
					);

				}
			);

		}



		this.element
			.querySelectorAll(
				'[data-admin-list-filter]'
			)
			.forEach(filter=>{


				filter.addEventListener(
					'change',
					()=>{


						this.filters[
							filter.dataset.adminListFilter
							]=filter.value;


						this.page=1;

						this.load();


					}
				);


			});



		this.element
			.querySelectorAll(
				'[data-admin-list-sort]'
			)
			.forEach(column=>{


				column.addEventListener(
					'click',
					()=>{


						const key =
							column.dataset.adminListSort;


						if(this.sort===key){


							this.direction =
								this.direction==='asc'
									?'desc'
									:'asc';


						}else{


							this.sort=key;

							this.direction='asc';


						}


						this.load();


					}
				);


			});



		this.pageSize?.addEventListener(
			'change',
			()=>{

				this.perPage =
					Number(
						this.pageSize.value
					);


				this.page=1;

				this.load();

			}
		);



		this.prevButton?.addEventListener(
			'click',
			()=>{

				if(this.page>1){

					this.page--;

					this.load();

				}

			}
		);



		this.nextButton?.addEventListener(
			'click',
			()=>{


				this.page++;

				this.load();


			}
		);



		this.element
			.querySelector(
				'[data-admin-list-clear-filters]'
			)
			?.addEventListener(
				'click',
				()=>{

					this.clearFilters();

				}
			);



		document.addEventListener(
			'click',
			event=>{


				if(
					!event.target.closest(
						'[data-admin-action-menu]'
					)
				){

					this.closeMenus();

				}


			}
		);


	}



	async load(){


		this.showLoading();


		try{


			const response =
				await this.request();


			this.render(
				response.data,
				response.meta
			);
			this.updateSortIndicators();


		}catch(error){


			this.showError(
				error.message
			);


		}finally{


			this.hideLoading();


		}


	}



	async request(){

		const params = new URLSearchParams();


		params.set(
			'page',
			this.page
		);


		params.set(
			'perPage',
			this.perPage
		);


		if(this.search){

			params.set(
				'search',
				this.search
			);

		}


		if(this.sort){

			params.set(
				'sort',
				this.sort
			);


			params.set(
				'direction',
				this.direction
			);

		}


		Object.entries(this.filters)
			.forEach(([key,value])=>{

				if(value){

					params.append(
						`filters[${key}]`,
						value
					);

				}

			});


		const response = await fetch(
			`${this.config.endpoint}?${params.toString()}`,
			{
				headers:{
					Accept:'application/json'
				}
			}
		);


		const json = await response.json();


		if(!json.success){

			throw new Error(
				json.message ??
				'Errore caricamento dati'
			);

		}


		return json;

	}

	updateSortIndicators()
	{

		this.element
			.querySelectorAll(
				'[data-admin-list-sort]'
			)
			.forEach(column=>{


				const asc =
					column.querySelector(
						'[data-sort-asc]'
					);


				const desc =
					column.querySelector(
						'[data-sort-desc]'
					);



				if(!asc || !desc){
					return;
				}



				asc.classList.remove(
					'text-blue-600'
				);

				desc.classList.remove(
					'text-blue-600'
				);



				if(
					column.dataset.adminListSort === this.sort
				){

					if(this.direction === 'asc'){

						asc.classList.add(
							'text-blue-600'
						);

					}else{

						desc.classList.add(
							'text-blue-600'
						);

					}

				}


			});

	}



	render(rows,meta){


		this.hideStates();


		if(this.total){

			this.total.textContent =
				meta.total;

		}



		if(!rows.length){


			if(
				this.search ||
				Object.values(this.filters)
					.some(value=>value)
			){

				this.showNoResults();

			}else{

				this.showEmpty();

			}


			this.body.innerHTML='';

			return;

		}



		this.renderRows(rows);

		this.renderPagination(meta);


	}



	renderRows(rows){


		this.body.innerHTML='';



		rows.forEach(row=>{


			const tr =
				document.createElement('tr');


			tr.className =
				'hover:bg-gray-50 cursor-pointer transition';


			tr.dataset.id =
				row.id;

			if(
				this.config.detailPanel &&
				this.config.detailEndpoint
			){
				tr.addEventListener(
					'click',
					()=>{

						document.dispatchEvent(
							new CustomEvent(
								'admin-detail:open',
								{
									detail:{
										id: row.id,
										endpoint:this.config.detailEndpoint,
										renderer:this.config.detailRenderer
									}
								}
							)
						);

					}
				);
			}

			tr.addEventListener(
				'dblclick',
				()=>{


					if(this.config.rowEdit && row.id){


						const url =
							this.config.rowEdit.replace(
								'{id}',
								row.id
							);


						window.location.href = url;


					}


				}
			);



			this.config.columns.forEach(column=>{


				const td =
					document.createElement('td');


				td.className =
					'px-6 py-4 text-sm text-gray-700 overflow-hidden';



				if(column.width){

					td.style.width =
						column.width;

				}



				td.appendChild(
					this.renderCell(
						row,
						column
					)
				);



				tr.appendChild(td);


			});



			if(
				this.config.actions?.length
			){


				const td =
					document.createElement('td');


				td.style.width='90px';


				td.className =
					'px-6 py-4 text-right whitespace-nowrap';



				td.appendChild(
					this.renderActions(row)
				);



				tr.appendChild(td);


			}



			this.body.appendChild(tr);


		});


	}

	openDetail(row)
	{

		document.dispatchEvent(
			new CustomEvent(
				'admin-detail:open',
				{
					detail:{
						id: row.id,
						endpoint: this.config.detailEndpoint
					}
				}
			)
		);

	}



	renderCell(row,column){


		const value =
			row[column.key];



		if(
			column.formatter &&
			this.formatters[column.formatter]
		){

			return this.renderFormatter(
				column.formatter,
				value
			);

		}



		if(column.type==='image'){

			return this.createImage(value);

		}



		if(column.type==='date'){

			return this.createText(
				this.formatDate(value)
			);

		}

		if(column.type==='datetime'){

			return this.createText(
				this.formatDateTime(value)
			);

		}



		return this.createText(
			value ?? ''
		);


	}



	createText(value){


		const span =
			document.createElement('span');


		span.className =
			'block truncate';


		span.textContent =
			value;


		span.title =
			value;


		return span;


	}



	renderFormatter(name,value){


		const data =
			this.formatters[name](value);


		const span =
			document.createElement('span');


		span.className =
			`inline-flex rounded-full px-2 py-1 text-xs font-medium ${data.className}`;


		span.textContent =
			data.label;


		return span;


	}



	renderActions(row){


		const wrapper =
			document.createElement('div');


		wrapper.className =
			'relative inline-block';


		wrapper.dataset.adminActionMenu='';



		const button =
			document.createElement('button');


		button.type='button';

		button.textContent='⋮';


		button.className =
			'rounded px-2 py-1 hover:bg-gray-100';



		const menu =
			document.createElement('div');


		menu.className =
			'hidden absolute right-0 z-20 mt-2 w-40 rounded-md border bg-white shadow-lg';



		this.config.actions.forEach(action=>{

			if(action.key === 'copy'){

				const form =
					document.createElement('form');


				form.method='POST';

				form.action =
					new URL(
						`/admin/events/copy/${row.id}`,
						window.location.origin
					).href;

				form.addEventListener(
					'click',
					event=>{
						event.stopPropagation();
					}
				);

				form.addEventListener(
					'submit',
					event=>{

						const confirmed =
							window.confirm(
								action.confirm ||
								'Vuoi creare una copia di questo elemento?'
							);


						if(!confirmed){
							event.preventDefault();
							return;
						}

					}
				);

				const token =
					document.createElement('input');


				token.type='hidden';

				token.name='csrf_token';

				token.value=
					this.config.csrfToken || '';

				const button =
					document.createElement('button');


				button.type='submit';

				button.textContent =
					action.label;

				button.className =
					'block w-full px-4 py-2 text-left text-sm text-blue-600 hover:bg-blue-50';

				button.addEventListener(
					'click',
					event=>{
						event.stopPropagation();
					}
				);

				form.appendChild(token);
				form.appendChild(button);
				menu.appendChild(form);
				return;
			}

			if(action.key === 'delete'){

				const form =
					document.createElement('form');


				form.method='POST';

				form.action=
					row._links?.delete ?? '#';

				form.addEventListener(
					'click',
					event=>{
						event.stopPropagation();
					}
				);

				form.addEventListener(
					'submit',
					async event=>{

						event.preventDefault();

						try{

							const confirmed =
								window.confirm(
									action.confirm ||
									'Sei sicuro di voler eliminare questo elemento?'
								);


							if(!confirmed){
								return;
							}


							const response =
								await fetch(
									form.action,
									{
										method:'POST',
										body:new FormData(form),
										headers:{
											'Accept':'application/json'
										}
									}
								);


							const json =
								await response.json();


							if(!json.success){
								throw new Error(
									json.message ||
									'Errore durante eliminazione'
								);
							}

							this.showToast(
								json.message ||
								'Elemento eliminato con successo.',
								'success'
							);

							this.load();

						}catch(error){

							console.error(error);

							alert(
								error.message ||
								'Errore durante eliminazione'
							);

						}

					}
				);

				const token =
					document.createElement('input');


				token.type='hidden';

				token.name='csrf_token';

				token.value=
					this.config.csrfToken || '';

				const button =
					document.createElement('button');


				button.type='submit';

				button.textContent =
					action.label;

				button.className =
					'block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50';

				button.addEventListener(
					'click',
					event=>{
						event.stopPropagation();
					}
				);


				form.appendChild(token);

				form.appendChild(button);

				menu.appendChild(form);

				return;

			}


			const link =
				document.createElement('a');


			link.textContent =
				action.label;


			link.href =
				row._links?.[action.key] ?? '#';


			link.className =
				'block px-4 py-2 text-sm hover:bg-gray-50';


			menu.appendChild(link);


		});



		button.onclick = event=>{


			event.stopPropagation();


			this.closeMenus();


			menu.classList.toggle(
				'hidden'
			);


		};



		wrapper.appendChild(button);

		wrapper.appendChild(menu);



		return wrapper;


	}



	renderPagination(meta){


		if(!this.pageNumbers){

			return;

		}


		this.pageNumbers.innerHTML='';



		this.getPages(
			meta.page,
			meta.pages
		)
			.forEach(page=>{


				const button =
					document.createElement('button');


				button.textContent =
					page;


				button.className =
					'px-3 py-2 text-sm';



				if(page===meta.page){

					button.classList.add(
						'font-bold'
					);

				}



				if(page!=='...'){

					button.onclick=()=>{

						this.page=page;

						this.load();

					};

				}


				this.pageNumbers.appendChild(button);


			});



		this.prevButton.disabled =
			meta.page<=1;


		this.nextButton.disabled =
			meta.page>=meta.pages;


	}



	getPages(current,total){


		if(total<=7){

			return Array.from(
				{
					length:total
				},
				(_,i)=>i+1
			);

		}



		const pages=[1];


		if(current>3){

			pages.push('...');

		}



		for(
			let i=Math.max(2,current-1);
			i<=Math.min(total-1,current+1);
			i++
		){

			pages.push(i);

		}



		if(current<total-2){

			pages.push('...');

		}



		pages.push(total);


		return pages;


	}



	createImage(src){


		const img =
			document.createElement('img');


		img.src =
			src;


		img.loading =
			'lazy';


		img.className =
			'h-10 w-10 rounded object-cover';



		return img;


	}



	formatDate(value){


		if(!value){

			return '';

		}


		return new Date(value)
			.toLocaleDateString(
				'it-IT'
			);


	}

	formatDateTime(value){

		if(!value){

			return '';

		}

		return new Date(String(value).replace(' ', 'T'))
			.toLocaleString(
				'it-IT',
				{
					day:'2-digit',
					month:'2-digit',
					year:'numeric',
					hour:'2-digit',
					minute:'2-digit'
				}
			);


	}



	clearFilters(){


		this.search='';

		this.filters={};

		this.page=1;


		if(this.searchInput){

			this.searchInput.value='';

		}



		this.element
			.querySelectorAll(
				'[data-admin-list-filter]'
			)
			.forEach(filter=>{

				filter.value='';

			});


		this.load();


	}



	closeMenus(){


		this.element
			.querySelectorAll(
				'[data-admin-action-menu] > div'
			)
			.forEach(menu=>{

				menu.classList.add(
					'hidden'
				);

			});


	}



	hideStates(){

		this.empty?.classList.add(
			'hidden'
		);

		this.noResults?.classList.add(
			'hidden'
		);

	}



	showLoading(){

		this.loading?.classList.remove(
			'hidden'
		);

	}



	hideLoading(){

		this.loading?.classList.add(
			'hidden'
		);

	}



	showEmpty(){

		this.empty?.classList.remove(
			'hidden'
		);

	}



	showNoResults(){

		this.noResults?.classList.remove(
			'hidden'
		);

	}



	showError(message){


		this.body.innerHTML =
			`<tr>
				<td colspan="100"
					class="px-6 py-8 text-center text-red-600">
					${message}
				</td>
			</tr>`;


	}

}



document
	.querySelectorAll(
		'[data-admin-list]'
	)
	.forEach(element=>{

		const instance =
			new AdminList(element);


		element.adminList =
			instance;

	});

document.addEventListener(
	'admin-list:reload',
	()=>{

		document
			.querySelectorAll(
				'[data-admin-list]'
			)
			.forEach(
				element=>{

					element.adminList?.load();

				}
			);

	}
);

function showAdminToast(message, type = 'success') {
	const toast = document.getElementById('admin-toast');
	if (!toast) {
		return;
	}

	toast.className = `admin-toast ${type}`;
	toast.textContent = message;
	requestAnimationFrame(() => toast.classList.add('visible'));

	clearTimeout(window.__adminToastTimer);
	window.__adminToastTimer = setTimeout(() => {
		toast.classList.remove('visible');
	}, 2400);
}

function bindAdminAjaxForms() {
	document.querySelectorAll('form[data-ajax-submit="true"]').forEach((form) => {
		if (form.dataset.ajaxBound === 'true') {
			return;
		}
		form.dataset.ajaxBound = 'true';

		form.addEventListener('submit', async (event) => {
			event.preventDefault();

			const submitButton = form.querySelector('[type="submit"]');
			const originalText = submitButton ? submitButton.textContent : '';
			const successRedirect = form.dataset.successRedirect || '';

			if (submitButton) {
				submitButton.disabled = true;
				submitButton.textContent = 'Salvataggio...';
			}

			try {
				const response = await fetch(form.action, {
					method: (form.method || 'POST').toUpperCase(),
					body: new FormData(form),
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'Accept': 'application/json'
					}
				});

				const contentType = response.headers.get('content-type') || '';
				const payload = contentType.includes('application/json')
					? await response.json()
					: null;

				if (!response.ok || (payload && payload.success === false)) {
					const message = payload?.message || 'Operazione non riuscita.';
					showAdminToast(message, 'error');
					return;
				}

				const message = payload?.message || 'Operazione completata con successo.';
				showAdminToast(message, 'success');

				if (successRedirect) {
					window.location.href = successRedirect;
				}

			} catch (error) {
				showAdminToast('Errore di rete durante il salvataggio.', 'error');
			} finally {
				if (submitButton) {
					submitButton.disabled = false;
					submitButton.textContent = originalText;
				}
			}
		});
	});
}

document.addEventListener('DOMContentLoaded', bindAdminAjaxForms);
