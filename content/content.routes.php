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
			$this->addScriptToHead(URL . '/extensions/url_router/assets/urlrouter.preferences.js', 400, false);

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('URL Router'))));
			$this->appendSubheading(__('URL Router'));

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
				$divcontent->setAttribute('class', 'content');
				$divcontent->appendChild($divgroup);

				$recontent = clone $divcontent;

				$regroup = new XMLElement('div');
				$regroup->setAttribute('class', 'group');
				$label = Widget::Label();
				$input = Widget::Input('settings[url-router][routes][][http301]', 'yes', 'checkbox');
				$label->setValue($input->generate() . ' ' . __('Send an HTTP 301 Redirect'));
				$regroup->appendChild($label);
				$recontent->appendChild($regroup);

				$divgroup = new XMLElement('div');
				$divgroup->setAttribute('class', 'group');
				$label = Widget::Label();
				$input = Widget::Input('settings[url-router][routes][][http301]', 'yes', 'checkbox');
				$label->setValue($input->generate() . ' ' . __('Force re-route even if page exists'));
				$divgroup->appendChild($label);
				$divcontent->appendChild($divgroup);

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
							if($route['type'] == 'redirect')
							{
								$header = new XMLElement('header', __('Redirect'));
							}
							else
							{
								$header = new XMLElement('header', __('Route'));
							}

							$hidden = Widget::Input("settings[url-router][routes][][type]", $route['type'], 'hidden');

							$li = new XMLElement('li');
							$li->setAttribute('class', 'instance expanded');
							$li->appendChild($header);
							$li->appendChild($hidden);

							$divcontent = new XMLElement('div');
							$divcontent->setAttribute('class', 'content');

							$divgroup = new XMLElement('div');
							$divgroup->setAttribute('class', 'group');

							$from = $route['from'];
							if (isset($route['from-clean'])) $from = $route['from-clean'];
							
							$labelfrom = Widget::Label(__('From'));
							$labelfrom->appendChild(Widget::Input("settings[url-router][routes][][from]", General::sanitize($from)));
							$labelfrom->appendChild(new XMLElement('p', __('Simplified: <code>page-name/:user/projects/:project</code>'), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));
							$labelfrom->appendChild(new XMLElement('p', __('Regular expression: <code>/\\/page-name\\/(.+\\/)/</code> Wrap in <code>/</code> and ensure to escape metacharacters with <code>\\</code>'), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));

							$to = $route['to'];
							if (isset($route['to-clean'])) $to = $route['to-clean'];
							
							$labelto = Widget::Label(__('To'));
							$labelto->appendChild(Widget::Input("settings[url-router][routes][][to]", General::sanitize($to)));
							$labelto->appendChild(new XMLElement('p', __('Simplified: <code>/new-page-name/:user/:project</code>'), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));
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
							else
							{
								$divgroup = new XMLElement('div');
								$divgroup->setAttribute('class', 'group');

								$label = Widget::Label();
								$input = Widget::Input('settings[url-router][routes][][http301]', 'yes', 'checkbox');
								if($route['http301'] == 'yes')
								{
									$input->setAttribute('checked', 'checked');
								}
								$label->setValue($input->generate() . ' ' . __('Force re-route even if page exists'));
								$divgroup->appendChild($label);
								$divcontent->appendChild($divgroup);
							}
							$li->appendChild($divcontent);
							$ol->appendChild($li);
						}
					}
				}

				$group->appendChild($ol);

				$fieldset->appendChild($group);

			$this->Form->appendChild($fieldset);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');

			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', $attr));

			$this->Form->appendChild($div);

		}
	}

?>
