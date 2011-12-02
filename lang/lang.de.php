<?php

	$about = array(
		'name' => 'Deutsch',
		'author' => array(
			'name' => 'Nils Werner',
			'email' => 'nils.werner@gmail.com',
			'website' => 'http://www.phoque.de'
		),
		'release-date' => '2011-12-02'
	);

	/**
	 * URL Router
	 */
	$dictionary = array(

		'Redirect' => 
		'Weiterleitung',

		'Route' => 
		'Umlenkung',

		'URL Router' => 
		'URL Router',

		// Missing

		'Routes' => 
		'Routen',

		'Choose between a <strong>Route</strong>, which silently shows the content under the original URL, or a <strong>Redirect</strong> which will actually redirect the user to the new URL.' => 
		'Wählen Sie zwischen einer <strong>Umlenkung</strong>, welche den Inhalt transparent unter der ursprünglichen URL darstellt oder einer <strong>Weiterleitung</strong>, die den Nutzer tatsächlich auf die neue URL weiterleitet.',

		'From' => 
		'Von',

		'Simplified: <code>page-name/:user/projects/:project</code>' => 
		'Vereinfacht: <code>seiten-name/:nutzer/projekte/:projekt</code>',

		'Regular expression: <code>/\\/page-name\\/(.+\\/)/</code> Wrap in <code>/</code> and ensure to escape metacharacters with <code>\\</code>' => 
		'Regulärer Ausdruck: <code>/\\/page-name\\/(.+\\/)/</code> Mit <code>/</code> umgeben und Steuerzeichen mit <code>\\</code> auszeichen',

		'To' => 
		'Nach',

		'Simplified: <code>/new-page-name/:user/:project</code>' => 
		'Vereinfacht: <code>/neuer-seiten-name/:nutzer/:projekt</code>',

		'Regular expression: <code>/new-page-name/$1/</code>' => 
		'Regulärer Ausdruck: <code>/neuer-seiten-name/$1/</code>',

		'Send an HTTP 301 Redirect' => 
		'HTTP 301 Weiterleitung senden',

		'Force re-route even if page exists' => 
		'Umlenken, auch wenn Seite existiert',

	);
