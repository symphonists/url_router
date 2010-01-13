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
			//$page = $context['parent']->Page;
			/*echo "<pre>";
			print_r($context);		
			echo "</pre>";*/
			/*$rules = array(
				array(
					"from" => "/\/vanity\/(.*?)/i",
					"to" => "/article/clean-up-your-feed-list/$1"
				)
			);*/
			$routes = $this->getRoutes();
			$rules = $routes['routes'];
			#var_dump($rules);
			$url = $context['page'];
			#echo "URL IS #$url#";
			#echo "<br />";
			$matches = array();
			foreach($rules as $rule) {
				#echo "RULE IS #{$rule['from']}#";
				#echo "<br />";
				if(preg_match($rule['from'], $url, &$matches) == 1) {
					#echo "got here";
					$new_url = preg_replace($rule['from'], $rule['to'], $url);
				}
			}
			#echo "NEW URL IS #$new_url#";
			if($new_url) $context['page'] = $new_url;
			//if($context['page'] == "/vanity/") $context['page'] = "/article/clean-up-your-feed-list";
		}
			
	}

?>
