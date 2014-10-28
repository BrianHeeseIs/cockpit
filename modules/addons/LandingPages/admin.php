<?php

// ACL
$this("acl")->addResource('LandingPages', ['manage.landingpages', 'manage.entries']);

$app->on('admin.init', function () {

	if (!$this->module('auth')->hasaccess('LandingPages', ['manage.landingpages', 'manage.entries'])) {return;
	}

	// bind controllers
	$this->bindClass('LandingPages\\Controller\\LandingPages', 'landingpages');
	$this->bindClass('LandingPages\\Controller\\Api', 'api/landingpages');

	$this('admin')->menu('top', [
		'url' => $this->routeUrl('/landingpages'),
		'label' => '<i class="uk-icon-list"></i>',
		'title' => $this('i18n')->get('LandingPages'),
		'active' => (strpos($this['route'], '/landingpages') === 0)
	], 5);

	// handle global search request
	$this->on('cockpit.globalsearch', function ($search, $list) {

		foreach ($this->db->find('common/landingpages') as $c) {
			if (stripos($c['name'], $search) !== false) {
				$list[] = [
					'title' => '<i class="uk-icon-list"></i> ' . $c['name'],
					'url' => $this->routeUrl('/landingpages/entries/' . $c['_id'])
				];
			}
		}
	});

});

$app->on('admin.dashboard.aside', function () {

	if (!$this->module('auth')->hasaccess('LandingPages', ['manage.landingpages', 'manage.entries'])) {return;
	}

	$title = $this('i18n')->get('LandingPages');
	$badge = $this->db->getCollection('common/landingpages')->count();
	$landingpages = $this->db->find('common/landingpages', ['limit' => 3, 'sort' => ['created' => -1]])->toArray();

	$this->renderView('landingpages:views/dashboard.php with cockpit:views/layouts/dashboard.widget.php', compact('title', 'badge', 'landingpages'));
});

// register content fields
$app->on("cockpit.content.fields.sources", function () {

	echo $this->assets([
		'landingpages:assets/field.linkcollection.js',
	], $this['cockpit/version']);

});
