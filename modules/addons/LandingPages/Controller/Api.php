<?php

namespace LandingPages\Controller;

class Api extends \Cockpit\Controller {

	public function find() {

		$options = [];

		if ($filter = $this->param("filter", null)) {$options["filter"] = $filter;
		}

		if ($limit = $this->param("limit", null)) {$options["limit"] = $limit;
		}

		if ($sort = $this->param("sort", null)) {$options["sort"] = $sort;
		}

		if ($skip = $this->param("skip", null)) {$options["skip"] = $skip;
		}

		$docs = $this->app->db->find("common/landingpages", $options);

		if (count($docs) && $this->param("extended", false)) {
			foreach ($docs as &$doc) {
				$doc["count"] = $this->app->module("landingpages")->collectionById($doc["_id"])->count();
			}
		}

		return json_encode($docs->toArray());
	}

	public function findOne() {

		$doc = $this->app->db->findOne("common/landingpages", $this->param("filter", []));

		return $doc ? json_encode($doc) : '{}';
	}

	public function save() {

		$landingpage = $this->param("collection", null);

		if ($landingpage) {

			$landingpage["modified"] = time();
			$landingpage["_uid"] = @$this->user["_id"];

			if (!isset($landingpage["_id"])) {
				$landingpage["created"] = $landingpage["modified"];
			}

			$this->app->db->save("common/landingpages", $landingpage);
		}

		return $landingpage ? json_encode($landingpage) : '{}';
	}

	public function update() {

		$criteria = $this->param("criteria", false);
		$data = $this->param("data", false);

		if ($criteria && $data) {
			$this->app->db->update("common/landingpages", $criteria, $data);
		}

		return '{"success":true}';
	}

	public function remove() {

		$landingpage = $this->param("collection", null);

		if ($landingpage) {
			$col = "collection" . $landingpage["_id"];

			$this->app->db->dropLandingPage("landingpages/{$col}");
			$this->app->db->remove("common/landingpages", ["_id" => $landingpage["_id"]]);
		}

		return $landingpage ? '{"success":true}' : '{"success":false}';
	}

	public function duplicate() {

		$landingpageId = $this->param("collectionId", null);

		if ($landingpageId) {

			$landingpage = $this->app->db->findOneById("common/landingpages", $landingpageId);

			if ($landingpage) {

				unset($landingpage['_id']);
				$landingpage["modified"] = time();
				$landingpage["_uid"] = @$this->user["_id"];
				$landingpage["created"] = $landingpage["modified"];

				$landingpage["name"] .= ' (copy)';

				$this->app->db->save("common/landingpages", $landingpage);

				return json_encode($landingpage);
			}
		}

		return false;
	}

	public function entries() {

		$landingpage = $this->param("landingpage", null);
		$entries = [];

		if ($landingpage) {

			$col = "landingpage" . $landingpage["_id"];
			$options = [];

			if ($landingpage["sortfield"] && $landingpage["sortorder"]) {
				$options["sort"] = [];
				$options["sort"][$landingpage["sortfield"]] = (int) $landingpage["sortorder"];
			}

			if ($filter = $this->param("filter", null)) {$options["filter"] = is_string($filter) ? json_decode($filter, true) : $filter;
			}

			if ($limit = $this->param("limit", null)) {$options["limit"] = $limit;
			}

			if ($sort = $this->param("sort", null)) {$options["sort"] = $sort;
			}

			if ($skip = $this->param("skip", null)) {$options["skip"] = $skip;
			}

			$entries = $this->app->db->find("landingpages/{$col}", $options);
		}

		return json_encode($entries->toArray());
	}

	public function removeentry() {

		$landingpage = $this->param("collection", null);
		$entryId = $this->param("entryId", null);

		if ($landingpage && $entryId) {

			$colid = $landingpage["_id"];
			$col = "collection" . $landingpage["_id"];

			$this->app->db->remove("landingpages/{$col}", ["_id" => $entryId]);

			$this->app->helper("versions")->remove("coentry:{$colid}-{$entryId}");
		}

		return ($landingpage && $entryId) ? '{"success":true}' : '{"success":false}';
	}

	public function emptytable() {

		$landingpage = $this->param("collection", null);

		if ($landingpage) {

			$landingpage = "collection" . $landingpage["_id"];

			$this->app->db->remove("landingpages/{$landingpage}", []);
		}

		return $landingpage ? '{"success":true}' : '{"success":false}';
	}

	public function saveentry() {

		$landingpage = $this->param("collection", null);
		$entry = $this->param("entry", null);

		if ($landingpage && $entry) {

			$entry["modified"] = time();
			$entry["_uid"] = @$this->user["_id"];

			$col = "collection" . $landingpage["_id"];

			if (!isset($entry["_id"])) {
				$entry["created"] = $entry["modified"];
			} else {

				if ($this->param("createversion", null)) {
					$id = $entry["_id"];
					$colid = $landingpage["_id"];

					$this->app->helper("versions")->add("coentry:{$colid}-{$id}", $entry);
				}
			}

			$this->app->db->save("landingpages/{$col}", $entry);
		}

		return $entry ? json_encode($entry) : '{}';
	}

	// Versions

	public function getVersions() {

		$return = [];
		$id = $this->param("id", false);
		$colid = $this->param("colId", false);

		if ($id && $colid) {

			$versions = $this->app->helper("versions")->get("coentry:{$colid}-{$id}");

			foreach ($versions as $uid => $data) {
				$return[] = ["time" => $data["time"], "uid" => $uid];
			}
		}

		return json_encode(array_reverse($return));

	}

	public function clearVersions() {

		$id = $this->param("id", false);
		$colid = $this->param("colId", false);

		if ($id && $colid) {
			return '{"success":' . $this->app->helper("versions")->remove("coentry:{$colid}-{$id}") . '}';
		}

		return '{"success":false}';
	}

	public function restoreVersion() {

		$versionId = $this->param("versionId", false);
		$docId = $this->param("docId", false);
		$colId = $this->param("colId", false);

		if ($versionId && $docId && $colId) {

			if ($versiondata = $this->app->helper("versions")->get("coentry:{$colId}-{$docId}", $versionId)) {

				$col = "collection" . $colId;

				if ($entry = $this->app->db->findOne("landingpages/{$col}", ["_id" => $docId])) {
					$this->app->db->save("landingpages/{$col}", $versiondata["data"]);
					return '{"success":true}';
				}
			}
		}

		return false;
	}

	public function export($landingpageId) {

		if (!$this->app->module("auth")->hasaccess("landingpages", 'manage.landingpages')) {
			return false;
		}

		$landingpage = $this->app->db->findOneById("common/landingpages", $landingpageId);

		if (!$landingpage) {return false;
		}

		$col = "collection" . $landingpage["_id"];
		$entries = $this->app->db->find("landingpages/{$col}");

		return json_encode($entries, JSON_PRETTY_PRINT);
	}

	public function updateGroups() {

		$groups = $this->param("groups", false);

		if ($groups !== false) {

			$this->app->memory->set("cockpit.landingpages.groups", $groups);

			return '{"success":true}';
		}

		return false;
	}

	public function getGroups() {

		$groups = $this->app->memory->get("cockpit.landingpages.groups", []);

		return json_encode($groups);
	}
}