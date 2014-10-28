<?php

// API

$this->module("landingpages")->extend([

	'get_collection' => function ($name) use ($app) {

		static $landingpages;

		if (null === $landingpages) {
			$landingpages = [];
		}

		if (!isset($landingpages[$name])) {
			$landingpages[$name] = $app->db->findOne('common/landingpages', ['name' => $name]);
		}

		return $landingpages[$name];
	},

	'collection' => function ($name) use ($app) {

		$collection = $this->get_collection($name);

		if ($collection) {
			$col = 'collection' . $collection["_id"];
			$collection = $app->db->getCollection("landingpages/{$col}");
		}

		return $collection ? $collection : null;
	},

	'collectionById' => function ($colid) use ($app) {

		static $landingpages;

		if (null === $landingpages) {
			$landingpages = [];
		}

		if (!isset($landingpages[$colid])) {

			$collection = "collection{$colid}";
			$landingpages[$colid] = $app->db->getCollection("landingpages/{$collection}");

		}

		return $landingpages[$colid];
	},

	'landingpages' => function ($options = []) {

		$return = [];
		$landingpages = $app->db->find('common/landingpages', $options)->toArray();

		foreach ($landingpages as $collection) {
			$return[$collection['name']] = $this->collectionById($collection['_id']);
		}

		return $return;
	},

	'get_collection_by_slug' => function ($slug) use ($app) {

		$collection = $app->db->findOne('common/landingpages', ['slug' => $slug]);

		if ($collection) {
			$collection = "collection" . $collection["_id"];
			return $app->db->getCollection("landingpages/{$collection}");
		}

		return null;
	},

	'group' => function ($group, $sort = null) use ($app) {

		return $this->landingpages(['filter' => ['group' => $group], 'sort' => $sort]);
	},

	'populate' => function ($collection, $resultset) use ($app) {

		static $cache;

		if (null === $cache) {
			$cache = [];
		}

		// check if resultset is a cursor object
		if (is_object($resultset)) {
			if (is_a($resultset, 'MongoLite\\Cursor')) {$resultset = $resultset->toArray();
			}

			if (is_a($resultset, 'MongoCursor')) {$resultset = iterator_to_array($resultset);
			}
		}

		if (!count($resultset)) {
			return $resultset;
		}

		$collection = $this->get_collection($collection);

		if (!$collection) {
			return $resultset;
		}

		$hasOne = [];
		$hasMany = [];

		foreach ($collection['fields'] as &$field) {

			if ($field['type'] == 'link-collection') {

				if (isset($field['multiple']) && $field['multiple']) {
					$hasMany[$field['name']] = $field['collection'];
				} else {
					$hasOne[$field['name']] = $field['collection'];
				}
			}
		}

		foreach ($resultset as &$doc) {

			// resolve hasOne
			foreach ($hasOne as $f => $colid) {

				if (isset($doc[$f]) && $doc[$f]) {

					if (!isset($cache[$colid][$doc[$f]])) {
						$cache[$colid][$doc[$f]] = $this->collectionById($colid)->findOne(['_id' => $doc[$f]]);
					}

					$doc[$f] = $cache[$colid][$doc[$f]];
				}
			}

			// resolve hasMany
			foreach ($hasMany as $f => $colid) {

				if (isset($doc[$f]) && $doc[$f] && is_array($doc[$f])) {

					$col = $this->collectionById($colid);

					foreach ($doc[$f] as $index => $_id) {

						if (!isset($cache[$colid][$_id])) {

							$cache[$colid][$_id] = $col->findOne(['_id' => $_id]);
						}

						$doc[$f][$index] = $cache[$colid][$_id];
					}
				}
			}
		}

		return $resultset;
	},

	'populateOne' => function ($collection, $item) use ($app) {

		if (!$item) {
			return $item;
		}

		$item = $this->populate($collection, [$item]);

		return $item[0];
	},

	'getLinked' => function ($colId, $itemId) {

		static $cache;

		$result = null;
		$collection = $this->collectionById($colId);

		if (!$collection) {
			return $result;
		}

		if (is_array($itemId)) {

			$result = [];

			foreach ($itemId as &$id) {

				if (!isset($cache[$colId][$id])) {

					$cache[$colId][$id] = $collection->findOne(['_id' => $id]);
				}

				$result[$id] = $cache[$colId][$id];
			}

		} else {

			if (!isset($cache[$colId][$itemId])) {

				$cache[$colId][$itemId] = $collection->findOne(['_id' => $itemId]);
			}

			$result = $cache[$colId][$itemId];
		}

		return $result;
	},

	'find' => function ($collection, $options = []) {

		$collection = $this->get_collection($collection);

		if (!$collection) {return false;
		}

		$col = "collection" . $collection["_id"];

		return $this->app->db->find("landingpages/{$col}", $options);
	},

	'findOne' => function ($collection, $criteria = []) {

		$collection = $this->get_collection($collection);

		if (!$collection) {return false;
		}

		$col = "collection" . $collection["_id"];

		return $this->app->db->findOne("landingpages/{$col}", $criteria);
	},

	'save_entry' => function ($collection, $data) {

		$collection = $this->get_collection($collection);

		if ($collection && $data) {

			$data['modified'] = time();
			$col = 'collection' . $collection["_id"];

			if (!isset($data["_id"])) {

				$data["created"] = $data["modified"];

				if (isset($collection["fields"])) {
					foreach ($collection["fields"] as $field) {

						if (!isset($data[$field['name']])) {
							$data[$field['name']] = isset($field['default']) ? $field['default'] : '';
						}
					}
				}
			}

			return $this->app->db->save("landingpages/{$col}", $data);
		}

		return false;
	},

	'remove' => function ($collection, $criteria) {

		$collection = $this->get_collection($collection);

		if (!$collection) {return false;
		}

		$col = "collection" . $datastore["_id"];

		return $this->app->db->remove("landingpages/{$col}", $criteria);
	}
]);

if (!function_exists('collection')) {
	function collection($name) {
		return cockpit('landingpages')->collection($name);
	}
}

if (!function_exists('collection_populate')) {
	function collection_populate($collection, $resultset) {
		return cockpit('landingpages')->populate($collection, $resultset);
	}

	function collection_populate_one($collection, $item) {
		return cockpit('landingpages')->populateOne($collection, $item);
	}
}

// REST
$app->on('cockpit.rest.init', function ($routes) {
	$routes["landingpages"] = 'landingpages\\Controller\\RestApi';
});

// ADMIN
if (COCKPIT_ADMIN && !COCKPIT_REST) {include_once (__DIR__ . '/admin.php');
}
