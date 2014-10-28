<?php

namespace landingpages\Controller;

class RestApi extends \LimeExtra\Controller {

	public function get($collection = null) {

		if (!$collection) {
			return false;
		}

		$collection = $this->app->db->findOne("common/landingpages", ["name" => $collection]);

		if (!$collection) {
			return false;
		}

		$entries = [];

		if ($collection) {

			$col = "collection" . $collection["_id"];
			$options = [];

			if ($filter = $this->param("filter", null)) {$options["filter"] = $filter;
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

}