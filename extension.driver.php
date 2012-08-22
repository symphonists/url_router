<?php

	Class Extension_Url_Router extends Extension{

		private $_hasrun = false;

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

		public function fetchNavigation() {
			return array(
				array(
					'location' => __('Blueprints'),
					'name' => __('URL Routes'),
					'link' => '/routes/'
				)
			);
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
				)
			);
		}

		/**
		 * Get all routes
		 *
		 * @return Array	Array of routes from the database
		 */
		public function getRoutes()
		{
			$routes = Symphony::Database()->fetch("SELECT * FROM tbl_url_router");

			foreach ($routes as $i => $route)
			{
				preg_match_all('/[$:]([0-9a-zA-Z_]+)/', $route['from'], $names, PREG_PATTERN_ORDER);
				$names = $names[0];

				if (!$names) continue;

				$new  = preg_replace('/[$:][[0-9a-zA-Z_]+/', '([a-zA-Z0-9_\+\-%]+)', $route['from']);
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
		 * @return Array	Array of matched page details
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
		public function saveRoutes()
		{
			$routes = array();

			if(!empty($_POST['settings']['url-router']['routes']))
			{
				$route = array(
					'type' => '',
					'from' => '',
					'to' => '',
					'http301' => 'no'
				);

				foreach($_POST['settings']['url-router']['routes'] as $item)
				{
					if(isset($item['type']) && !empty($item['type']))
					{
						$route = array(
							'type' => '',
							'from' => '',
							'to' => '',
							'http301' => 'no'
						);

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
				unset($_POST['settings']['url-router']['routes']);
			}
		}

		public function frontendPrePageResolve($context)
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
						$context['page'] = $route['routed'];
						if($route['http301'] === 'yes')
						{
							header("Location:" . URL . $context['page'], true, 301);
						}
						else
						{
							header("Location:" . URL . $context['page']);
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

		private function __getIndexPage()
		{
			return Symphony::Database()->fetchRow(0, "
				SELECT `tbl_pages`.* FROM `tbl_pages`, `tbl_pages_types`
				WHERE `tbl_pages_types`.page_id = `tbl_pages`.id
				AND tbl_pages_types.`type` = 'index'
				LIMIT 1
			");
		}

	}
