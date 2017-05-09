<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	class contentExtensionUrl_routerRoutes extends AdministrationPage {

		public function __construct(){
			parent::__construct();

			$this->_driver = Symphony::ExtensionManager()->create('url_router');
		}

		public function __actionIndex() {
			$this->_driver->saveRoutes();
		}

		public function __viewIndex() {
			$this->setPageType('form');
			$this->addScriptToHead(URL . '/extensions/url_router/assets/url_router.preferences.js', 400, false);

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('URL Router'))));
			$this->appendSubheading(__('URL Router'));

			if(isset($this->_context[1]) == 'saved') {
				$this->pageAlert(
					__('Routes saved at %s.', array(Widget::Time()->generate()))
					, Alert::SUCCESS);
			}

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('URL Routes')));

			$fieldset->appendChild(new XMLElement('p', __('Choose between a <strong>Route</strong>, which silently shows the content under the original URL, or a <strong>Redirect</strong> which will actually redirect the user to the new URL.'), array('class' => 'help')));

			$group = new XMLElement('div');
			$group->setAttribute('class', 'frame');

			$ol = new XMLElement('ol');
			$ol->setAttribute('data-name', __('Add route'));
			$ol->setAttribute('data-type', __('Remove route'));

			//	Redirect Template
			$li_re = new XMLElement('li');
			$li_re->setAttribute('class', 'template');
			$li_re->setAttribute('data-name', 'Redirect');
			$li_re->setAttribute('data-type', 'redirect');
			$header_re = new XMLElement('header', __('Redirect'));
			$hidden_re = Widget::Input("settings[url-router][routes][][type]", 'redirect', 'hidden');
			$li_re->appendChild($header_re);
			$li_re->appendChild($hidden_re);

			//	Route Template
			$li_ro = new XMLElement('li');
			$li_ro->setAttribute('class', 'template');
			$li_ro->setAttribute('data-name', 'Route');
			$li_ro->setAttribute('data-type', 'route');
			$header_ro = new XMLElement('header', __('Route'));
			$hidden_ro = Widget::Input("settings[url-router][routes][][type]", 'route', 'hidden');
			$li_ro->appendChild($header_ro);
			$li_ro->appendChild($hidden_ro);

			//	From To boxes
			$divgroup = new XMLElement('div');
			$divgroup->setAttribute('class', 'group');

			$labelfrom = Widget::Label(__('From'));
			$labelfrom->appendChild(Widget::Input("settings[url-router][routes][][from]"));
			$labelfrom->appendChild(new XMLElement('p', __('Simplified: <code>page-name/$user/projects/$project</code>'), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));
			$labelfrom->appendChild(new XMLElement('p', __('Regular expression: <code>/\\/page-name\\/(.+\\/)/</code> Wrap in <code>/</code> and ensure to escape metacharacters with <code>\\</code>'), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));

			$labelto = Widget::Label(__('To'));
			$labelto->appendChild(Widget::Input("settings[url-router][routes][][to]"));
			$labelto->appendChild(new XMLElement('p', __('Simplified: <code>/new-page-name/$user/$project</code>'), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));
			$labelto->appendChild(new XMLElement('p', __('Regular expression: <code>/new-page-name/$1/</code>'), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));


			$divgroup->appendChild($labelfrom);
			$divgroup->appendChild($labelto);

			$divcontent = new XMLElement('div');
			$divcontent->appendChild($divgroup);

			$recontent = clone $divcontent;

			$regroup = new XMLElement('div');
			$regroup->setAttribute('class', 'group');
			$label = Widget::Label();
			$input = Widget::Input('settings[url-router][routes][][http301]', 'yes', 'checkbox');
			$label->setValue($input->generate() . ' ' . __('Send an HTTP 301 Redirect'));
			$regroup->appendChild($label);
			$recontent->appendChild($regroup);

			$li_re->appendChild($recontent);
			$li_ro->appendChild($divcontent);

			$ol->appendChild($li_ro);
			$ol->appendChild($li_re);

			if($routes = $this->_driver->getRoutes())
			{
				if(is_array($routes))
				{
					foreach($routes as $route)
					{
						$from = isset($route['from-clean']) ? $route['from-clean'] : $route['from'];
						$to = isset($route['to-clean']) ? $route['to-clean'] : $route['to'];

						$header = new XMLElement('header');
						$header->appendChild(new XMLElement('h4', $route['type'] == 'redirect' ?  __('Redirect') : __('Route') ));
						$header->appendChild(new XMLElement('span', __('From'), array('class' => 'type')));
						$header->appendChild(new XMLElement('span', $from, array('class' => 'type')));
						$header->appendChild(new XMLElement('span', __('To'), array('class' => 'type')));
						$header->appendChild(new XMLElement('span', $to, array('class' => 'type')));

						$hidden = Widget::Input("settings[url-router][routes][][type]", $route['type'], 'hidden');

						$li = new XMLElement('li');
						$li->setAttribute('class', 'instance expanded');
						$li->appendChild($header);
						$li->appendChild($hidden);

						$divcontent = new XMLElement('div');
						$divcontent->setAttribute('class', 'content');

						$divgroup = new XMLElement('div');
						$divgroup->setAttribute('class', 'group');

						$labelfrom = Widget::Label(__('From'));
						$labelfrom->appendChild(Widget::Input("settings[url-router][routes][][from]", General::sanitize($from)));
						$labelfrom->appendChild(new XMLElement('p', __('Simplified: <code>page-name/$user/projects/$project</code>'), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));
						$labelfrom->appendChild(new XMLElement('p', __('Regular expression: <code>/\\/page-name\\/(.+\\/)/</code> Wrap in <code>/</code> and ensure to escape metacharacters with <code>\\</code>'), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));

						$labelto = Widget::Label(__('To'));
						$labelto->appendChild(Widget::Input("settings[url-router][routes][][to]", General::sanitize($to)));
						$labelto->appendChild(new XMLElement('p', __('Simplified: <code>/new-page-name/$user/$project</code>'), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));
						$labelto->appendChild(new XMLElement('p', __('Regular expression: <code>/new-page-name/$1/</code>'), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));

						$divgroup->appendChild($labelfrom);
						$divgroup->appendChild($labelto);

						$divcontent->appendChild($divgroup);
						if($route['type'] == 'redirect')
						{
							$regroup = new XMLElement('div');
							$regroup->setAttribute('class', 'group');

							$label = Widget::Label();
							$input = Widget::Input('settings[url-router][routes][][http301]', 'yes', 'checkbox');
							if($route['http301'] == 'yes')
							{
								$input->setAttribute('checked', 'checked');
							}
							$label->setValue($input->generate() . ' ' . __('Send an HTTP 301 Redirect'));
							$regroup->appendChild($label);
							$divcontent->appendChild($regroup);
						}
						$li->appendChild($divcontent);
						$ol->appendChild($li);
					}
				}
			}

			$group->appendChild($ol);

			$fieldset->appendChild($group);

			$this->Form->appendChild($fieldset);

			$this->Header->setAttribute('class', 'spaced-bottom');
	        $this->Context->setAttribute('class', 'spaced-right');
	        $this->Contents->setAttribute('class', 'centered-content');
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');

			$div->appendChild(
				Widget::SVGIconContainer(
					'save',
					Widget::Input(
						'action[save]',
						__('Save Changes'),
						'submit',
						array('accesskey' => 's')
					)
				)
			);

			$div->appendChild(Widget::SVGIcon('chevron'));

			$this->Form->appendChild($div);

		}
	}

