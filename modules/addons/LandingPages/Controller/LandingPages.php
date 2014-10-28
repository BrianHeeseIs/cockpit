<?php

namespace landingpages\Controller;

class LandingPages extends \Cockpit\Controller {

	public function index() {
		return $this->render("landingpages:views/index.php");
	}

	public function landingpage($id = null) {

		if (!$this->app->module("auth")->hasaccess("landingpages", 'manage.landingpages')) {
			return false;
		}

		$locales = $this->app->db->getKey("cockpit/settings", "cockpit.locales", []);

		return $this->render("landingpages:views/collection.php", compact('id', 'locales'));
	}

	public function entries($id) {

		$collection = $this->app->db->findOne("common/landingpages", ["_id" => $id]);

		if (!$collection) {
			return false;
		}

		$count = $this->app->module("landingpages")->collectionById($collection["_id"])->count();

		$collection["count"] = $count;

		return $this->render("landingpages:views/entries.php", compact('id', 'collection', 'count'));
	}

	public function entry($collectionId, $entryId = null) {

		$collection = $this->app->db->findOne("common/landingpages", ["_id" => $collectionId]);
		$entry = null;

		if (!$collection) {
			return false;
		}

		if ($entryId) {
			$col = "collection" . $collection["_id"];
			$entry = $this->app->db->findOne("landingpages/{$col}", ["_id" => $entryId]);

			if (!$entry) {
				return false;
			}
		}

		$locales = $this->app->db->getKey("cockpit/settings", "cockpit.locales", []);

		return $this->render("landingpages:views/entry.php", compact('collection', 'entry', 'locales'));

	}

}
