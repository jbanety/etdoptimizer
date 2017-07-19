<?php
/**
 * @package      ETD Optimizer
 *
 * @version      2.7.0
 * @copyright    Copyright (C) 2012-2017 ETD Solutions. Tous droits réservés.
 * @license      Apache Version 2 (https://raw.githubusercontent.com/jbanety/etdoptimizer/master/LICENSE.md)
 * @author       ETD Solutions http://www.etd-solutions.com
 **/

function smarty_function_etdhook($params, Smarty_Internal_Template $template) {

	$zones = array();

	// On parse toutes les zones disponibles dans le hook.
	$pattern = '/<!--\[ETDHOOK:(.*)]-->(.*)<!--\[\/ETDHOOK:\1\]-->/isU';
	preg_match_all($pattern, $params['hook'], $matches, PREG_SET_ORDER);

	foreach($matches as $match) {
		$id = strtoupper('ETDHOOK_' . $match[1]);
		if (!isset($zones[$id])) {
			$zones[$id] = '';
		}
		$zones[$id] .= $match[2];
	}

	if (isset($params['zone'])) {
		$zone = strtoupper('ETDHOOK_' . $params['zone']);
		return $zones[$zone];
	}

	$tpl = _PS_THEME_DIR_ . 'hooks/' . strtolower($params['hookname']) . '.tpl';

	if (is_file($tpl)) {
		$template->assign($zones);
		$display = $template->fetch($tpl);
		return $display;
	}

	return $params['hook'];

}
