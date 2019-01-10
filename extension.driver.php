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
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
			return Symphony::Database()->query("DROP TABLE `tbl_url_router`");
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

				$to = $this->isExternal($route['to'])
					? $route['to']
					: '/'. trim($route['to'], '/');

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

					if($this->isExternal($route['to'])) {
						$route['external'] = true;
						$route['routed'] = rtrim($route['routed'], '/');
					}
					else {
						$route['external'] = false;
					}

					$return = $route;

					break;
				}
			}

			return $return;
		}

		public function isExternal($route) {
			return (strstr($route, 'http') !== false) ? true : false;
		}

		/**
		 * Filter out any params that are in the 'to' route as a querystring, unless the route contains 'http://'
		 * @param  String $route The 'to' route as a string
		 * @return String		 the filtered string, or full string if it contains 'http://'
		 */
		public function filterGetParams($route) {
			$return = $route;

			if(strpos($route, '?')) {
				$parts = preg_split('/(\?|&)/', $route);

				$return = $parts[0];
				unset($parts[0]);

				foreach($parts as $part) {
					if(!empty($part)) {
						$bits = explode('=', $part);

						$_GET[$bits[0]] = $bits[1];
					}
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

			redirect(SYMPHONY_URL . '/extension/url_router/routes/index/saved/');
		}

		public function frontendPrePageResolve($context)
		{
			if(!$this->_hasrun)
			{
				// Prevent an infinity loop of delegate callbacks to this function - @creativedutchmen
				$this->_hasrun = true;

				// Used to check page resolution, would cause loop.
				$frontend = Frontend::Page();

				// Get route or empty array
				$route = $this->getRoute($context['page']);

				// Check whether the current page resolves as it is
				$page_can_resolve = $frontend->resolvePage($context['page']);

				if(!empty($route))
				{
					// If it is not an external route
					if ($route['type'] == 'route' && $route['external'] === false) {
						// Check to see what has already resolved.
						if (isset($page_can_resolve['filelocation'])) {
							// Remove the PAGES path, .xsl and replace any _ with /.
							// Basically reconstruct the 'path' as if it was a URL request
							$resolved_path = str_replace(
								array(PAGES . '/', '.xsl', '_'),
								array('', '',  '/'),
								$page_can_resolve['filelocation']
							);

							// Now does the page we're routing from already exist in Symphony? No? Redirect.
							if (preg_match('~^' . preg_quote($resolved_path) . '~i', $route['original']) == false) {
								$route['routed'] = $this->filterGetParams($route['routed']);
								$context['page'] = $route['routed'];
							}
						}
						// Nothing exists in Symphony, redirect.
						else {
							$route['routed'] = $this->filterGetParams($route['routed']);
							$context['page'] = $route['routed'];
						}
					}
					// If is redirect or an external route
					elseif($route['type'] == 'redirect' || $route['external']) {
						$context['page'] = $route['routed'];
						$url = ($route['external'])
							? $context['page']
							: URL . $context['page'];

						if($route['http301'] === 'yes')
						{
							header("Location:" . $url, true, 301);
						}
						else
						{
							header("Location:" . $url);
						}
						exit;
					}
				}
				else
				{
					$index = PageManager::fetchPageByType('index');

					if(!$page_can_resolve)
					{
						$context['page'] = "/" . $index['handle'] . $context['page'];
					}
				}
				unset($frontend, $route, $page_can_resolve);
			}
		}
	}
