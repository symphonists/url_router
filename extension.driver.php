<?php

	Class Extension_Url_Router extends Extension{

		private $_hasrun = false;

		public function about()
		{
			return array('name' => 'URL Router',
				'version' => '1.2',
				'release-date' => '2011-07-08',
				'author' => array(
					'name' => 'Symphony Team',
					'website' => 'http://symphony-cms.com',
				),
				'description'   => 'Allows Regular Expression URL Routing in Symphony.'
			);
		}

		public function install()
		{
			Symphony::Database()->query("
					CREATE TABLE IF NOT EXISTS `tbl_url_router` (
						`id` int(11) NOT NULL auto_increment,
						`from` varchar(255) NOT NULL,
						`to` varchar(255) NOT NULL,
						`type` enum('route','redirect') DEFAULT 'route',
						`http301` enum('yes','no') DEFAULT 'no',
						PRIMARY KEY (`id`)
					)
			");
        }

		public function update($previousVersion)
		{
			if(version_compare($previousVersion, '1.1.0', '<'))
			{
				Symphony::Configuration()->remove('url-router');

				$type = Symphony::Database()->fetchVar('Field', 0, "SHOW COLUMNS FROM `tbl_url_router` LIKE 'type'");

				if(!$type)
				{
					return Symphony::Database()->query("
						ALTER TABLE `tbl_url_router`
						ADD `type` ENUM('route','redirect') DEFAULT 'route',
					");
				}

				return false;
			}

			if(version_compare($previousVersion, '1.1.1', '<'))
			{
				Symphony::Configuration()->remove('url-router');

				$type = Symphony::Database()->fetchVar('Field', 0, "SHOW COLUMNS FROM `tbl_url_router` LIKE 'http301'");

				if(!$type)
				{
					return Symphony::Database()->query("
						ALTER TABLE `tbl_url_router`
						ADD `http301` ENUM('yes','no') DEFAULT 'no'
					");
				}

				return false;
			}
		}

        public function uninstall()
		{
            Symphony::Database()->query("DROP TABLE `tbl_url_router`");
        }

		public function getSubscribedDelegates()
		{
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendPrePageResolve',
					'callback'	=> 'frontendPrePageResolve'
				),
				array(
					'page'		=> '/system/preferences/',
					'delegate'	=> 'AddCustomPreferenceFieldsets',
					'callback'	=> 'addCustomPreferenceFieldsets'
				),
				array(
					'page'      => '/system/preferences/',
					'delegate'  => 'Save',
					'callback'  => 'save'
				),
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'initaliseAdminPageHead'
				)
			);
		}

		/**
		 * Get all routes
		 *
		 * @return Array    Array of routes from the database
		 */
		public function getRoutes()
		{
			$routes = Symphony::Database()->fetch("SELECT * FROM tbl_url_router");

			foreach ($routes as $i => $route)
			{
				preg_match_all('/:([0-9a-zA-Z_]+)/', $route['from'], $names, PREG_PATTERN_ORDER);
				$names = $names[0];

				if (!$names) continue;

				$new  = preg_replace('/:[[0-9a-zA-Z_]+/', '([a-zA-Z0-9_\+\-%]+)', $route['from']);
				$new  = '/'. trim($new, '/');
				$new  = '/'. str_replace('/', "\/", $new);
				$new .= '/i';

				$to = '/'. trim($route['to'], '/');
				foreach ($names as $k => $n)
					$to = str_replace($n, '$'. ($k +1), $to);

				$route['from-clean'] = $route['from'];
				$route['to-clean'] = $route['to'];
				$route['from'] = $new;
				$route['to'] = $to;

				$routes[$i] = $route;
			}

			return $routes;
        }

		/**
		 * Get the first route that matches the given path
		 *
		 * @param String $path Thepath to regex match on
		 *
		 * @return Array    Array of matched page details
		 * @return Boolean	False if no page matched
		 */
		public function getRoute($path)
		{
			$routes = $this->getRoutes();

			$return = array();

			foreach($routes as $route)
			{
				if(preg_match($route['from'], $path, $matches) == 1)
				{
					$route['routed'] = preg_replace($route['from'], $route['to'], $path);

					$route['original'] = $path;

					$return = $route;

					break;
				}
			}

			return $return;
		}

		/**
		 * Save the routes from the preferences into the database
		 *
		 * @param unknown $context Symphony context
		 */
		public function save($context)
		{
			$routes = array();

			if(!empty($context['settings']['url-router']['routes']))
			{
				$route = array();

				foreach($context['settings']['url-router']['routes'] as $item)
				{
					if(isset($item['type']) && !empty($item['type']))
					{
						$route = array();

						$route['type'] = $item['type'];
					}
					if(isset($item['from']) && !empty($item['from']))
					{
						$route['from'] = $item['from'];
					}
					if(isset($item['to']) && !empty($item['to']))
					{
						$route['to'] = $item['to'];
						$routes[] = $route;
					}
					if(isset($item['http301']) && !empty($item['http301']))
					{
						$key = count($routes) - 1;
						$routes[$key]['http301'] = $item['http301'];
					}
				}
			}

			Symphony::Database()->query("DELETE FROM tbl_url_router");

			if(count($routes) > 0) {
				Symphony::Database()->insert($routes, "tbl_url_router");
				unset($context['settings']['url-router']['routes']);
			}
		}

		public function addCustomPreferenceFieldsets($context)
		{
			$allow = $this->allow();

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', 'URL Router'));

			$p = new XMLElement('p', 'Define regular expression rules for URL routing', array('class' => 'help'));
			$fieldset->appendChild($p);

			if($allow)
			{
				$p = new XMLElement('p', 'Choose between a <strong>Route</strong>, which silently redirects the content under the original URL, or a <strong>Redirect</strong> which will physically redirect to the new URL.');
				$fieldset->appendChild($p);

				$group = new XMLElement('div');
				$group->setAttribute('class', 'subsection');
				$group->appendChild(new XMLElement('p', __('Rules'), array('class' => 'label')));

				$ol = new XMLElement('ol');
				$ol->setAttribute('id', 'url-router-duplicator');
				$ol->setAttribute('class', 'orderable duplicator collapsible');

			//	Redirect Template
				$li_re = new XMLElement('li');
				$li_re->setAttribute('class', 'template');
				$h4_re = new XMLElement('h4', 'Redirect');
				$h4_re->setAttribute('class', 'header');
				$hidden_re = Widget::Input("settings[url-router][routes][][type]", 'redirect', 'hidden');
				$li_re->appendChild($h4_re);
				$li_re->appendChild($hidden_re);

			//	Route Template
				$li_ro = new XMLElement('li');
				$li_ro->setAttribute('class', 'template');
				$h4_ro = new XMLElement('h4', 'Route');
				$h4_ro->setAttribute('class', 'header');
				$hidden_ro = Widget::Input("settings[url-router][routes][][type]", 'route', 'hidden');
				$li_ro->appendChild($h4_ro);
				$li_ro->appendChild($hidden_ro);

			//	From To boxes
				$divgroup = new XMLElement('div');
				$divgroup->setAttribute('class', 'group');
				$labelfrom = Widget::Label(__('From'));
				$labelfrom->appendChild(Widget::Input("settings[url-router][routes][][from]"));
				$labelfrom->appendChild(new XMLElement('p', 'Example: "/\/page-name\/(.+\/)/" Wrap in / and ensure to escape metacharacters with \\', array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;
')));
				$labelto = Widget::Label(__('To'));
				$labelto->appendChild(Widget::Input("settings[url-router][routes][][to]"));
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
				$label->setValue($input->generate() . ' Send an HTTP 301 Redirect');
				$regroup->appendChild($label);
				$recontent->appendChild($regroup);

				$divgroup = new XMLElement('div');
				$divgroup->setAttribute('class', 'group');
				$label = Widget::Label();
				$input = Widget::Input('settings[url-router][routes][][http301]', 'yes', 'checkbox');
				$label->setValue($input->generate() . ' Force re-route if page exists');
				$divgroup->appendChild($label);
				$divcontent->appendChild($divgroup);

				$li_re->appendChild($recontent);
				$li_ro->appendChild($divcontent);

				$ol->appendChild($li_ro);
				$ol->appendChild($li_re);

				if($routes = $this->getRoutes())
				{
					if(is_array($routes))
					{
						foreach($routes as $route)
						{
							if($route['type'] == 'redirect')
							{
								$h4 = new XMLElement('h4', 'Redirect');
							}
							else
							{
								$h4 = new XMLElement('h4', 'Route');
							}

							$hidden = Widget::Input("settings[url-router][routes][][type]", $route['type'], 'hidden');

							$li = new XMLElement('li');
							$li->setAttribute('class', 'instance expanded');
							$h4->setAttribute('class', 'header');
							$li->appendChild($h4);
							$li->appendChild($hidden);

							$divcontent = new XMLElement('div');
							$divcontent->setAttribute('class', 'content');

							$divgroup = new XMLElement('div');
							$divgroup->setAttribute('class', 'group');

							$from = $route['from'];
							if (isset($route['from-clean'])) $from = $route['from-clean'];
							
							$labelfrom = Widget::Label(__('From'));
							$labelfrom->appendChild(Widget::Input("settings[url-router][routes][][from]", General::sanitize($from)));
							$labelfrom->appendChild(new XMLElement('p', 'Example: "/\/page-name\/(.+\/)/" Wrap in / and ensure to escape metacharacters with \\', array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;
')));
							$labelfrom->appendChild(new XMLElement('p', 'Example: "page-name/:user/projects/:project"', array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;
')));

							$to = $route['to'];
							if (isset($route['to-clean'])) $to = $route['to-clean'];
							
							$labelto = Widget::Label(__('To'));
							$labelto->appendChild(Widget::Input("settings[url-router][routes][][to]", General::sanitize($to)));
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
								$label->setValue($input->generate() . ' Send an HTTP 301 Redirect');
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
								$label->setValue($input->generate() . ' Force re-route if page exists');
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
			}
			else
			{
				$p = new XMLElement('p', 'This extension\'s code has been updated. For this extension to operate corrcectly, please update it on the Extensions page.');
				$fieldset->appendChild($p);
				$p = new XMLElement('p', '<strong>All current URL routing has been disabled due to changes to the code, and will not be re-enabled until the above step has been taken!</strong>');
				$fieldset->appendChild($p);
			}
			$context['wrapper']->appendChild($fieldset);
		}

		public function allow()
		{
			$installed = Symphony::Database()->fetchVar('version', 0, "SELECT `version` FROM `tbl_extensions` WHERE `name` = 'url_router'");

			return version_compare($installed, '1.1.0', '>=');
		}

		public function frontendPrePageResolve($context)
		{
			$allow = $this->allow();

			if($allow)
			{
				if(!$this->_hasrun)
				{
					// Prevent an infinity loop of delegate callbacks to this function - @creativedutchmen
					$this->_hasrun = true;

					// Used to check page resolution, would cause loop.
					$frontend = FrontEnd::Page();

					// Get route or empty array
					$route = $this->getRoute($context['page']);

					// Check whether the current page resolves as it is
					$page_can_resolve = $frontend->resolvePage($context['page']);

					if(!empty($route))
					{
						// If the page can resolve, but is route the route says to force
						if(!empty($page_can_resolve) && $route['type'] == 'route' && $route['http301'] == 'yes')
						{
							$context['page'] = $route['routed'];
						}
						// If the page can't resolve, and is route
						elseif(empty($page_can_resolve) && $route['type'] == 'route')
						{
							$context['page'] = $route['routed'];
						}
						// If is redirect
						elseif($route['type'] == 'redirect')
						{
							if($route['http301'] === 'yes')
							{
								header("Location:" . $context['page'], true, 301);
							}
							else
							{
								header("Location:" . $context['page']);
							}
							die;
						}
					}
					else
					{
						$index = $this->__getIndexPage();

						if(!$page_can_resolve)
						{
							$context['page'] = "/" . $index['handle'] . $context['page'];
						}
					}
					unset($frontend, $route, $page_can_resolve);
				}
			}
		}

		private function __getIndexPage()
		{
			return Symphony::Database()->fetchRow(0, "
				SELECT `tbl_pages`.* FROM `tbl_pages`, `tbl_pages_types`
				WHERE `tbl_pages_types`.page_id = `tbl_pages`.id
				AND tbl_pages_types.`type` = 'index'
				LIMIT 1
			");
		}

		public function initaliseAdminPageHead($context)
		{
			$page = $context['parent']->Page;
			if($page instanceof contentSystemPreferences)
			{
				$page->addScriptToHead(URL . '/extensions/url_router/assets/urlrouter.preferences.js', 400, false);
			}
		}
	}
