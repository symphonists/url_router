<?php

	Class Extension_Router extends Extension{
	
		public function about() {
			return array('name' => 'URL Router',
						 'version' => '0.1',
						 'release-date' => '2010-01-12',
						 'author' => array('name' => 'Robert Philp',
										   'website' => 'http://robertphilp.com',
										   'email' => ''),
							'description'   => 'Allows URL routing for vanity URLs etc'
				 		);
		}

		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendPrePageResolve',
					'callback'	=> 'frontendPrePageResolve'
				)
			);
		}

		public function getRoutes() {
            return $this->_Parent->Configuration->get('router');
        }

		public function frontendPrePageResolve($context) {
			$routes = $this->getRoutes();
			$rules = $routes['routes'];
			$url = $context['page'];
			$matches = array();
			foreach($rules as $rule) {
				if(preg_match($rule['from'], $url, &$matches) == 1) {
					$new_url = preg_replace($rule['from'], $rule['to'], $url);
				}
			}
			if($new_url) $context['page'] = $new_url;
		}
			
	}

?>
