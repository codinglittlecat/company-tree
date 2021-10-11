<?php

// define("COMPANY_API", 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies');
// define("TRAVEL_API", 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels');
define("COMPANY_API", 'company.json');
define("TRAVEL_API", 'travel.json');

function fetch_data($url)
{
    $response = file_get_contents($url);
    $result = json_decode($response, true);

    return $result;
}

// Travel Model

class Travel
{
    private $travels;
    private $costs = [];

    public function __construct($url = TRAVEL_API)
    {
        $this->travels = fetch_data($url);
        $this->group_costs_by_company_id();
    }

    public function group_costs_by_company_id()
    {
        foreach ($this->travels as $travel) {
            $company_id = $travel["companyId"];
            if (isset($this->costs[$company_id])) {
                $this->costs[$company_id] += $travel["price"];
            } else {
                $this->costs[$company_id] = $travel["price"];
            }
        }
    }

    public function get_cost($company_id)
    {
        if (isset($this->costs[$company_id])) {
            return $this->costs[$company_id];
        }
        return 0;
    }
}

// Company Model

class Company
{
    private $travel;
    private $companies;

    public function __construct($url = COMPANY_API)
    {
        $this->companies = fetch_data($url);
        $this->travel = new Travel();
    }

    public function generate_company_tree($parent_id = 0)
    {
        $tree = [];

        foreach ($this->companies as $company) {
            if ($company["parentId"] == $parent_id) {
                $company["cost"] = $this->travel->get_cost($company["id"]);
                $company["children"] = [];

                $children = $this->generate_company_tree($company["id"]);
                if ($children) {
                    $company["children"] = $children;
                    foreach ($children as $child) {
                        $company["cost"] += $child["cost"]; // Add children's travel cost.
                    }
                }

                // Pick only necessary data.
                $tree[] = [
                    "id" => $company["id"],
                    "name" => $company["name"],
                    "cost" => $company["cost"],
                    "children" => $company["children"],
                ];
            }
        }

        return $tree;
    }
}

class TestScript
{
    public function execute()
    {
        $start = microtime(true);
        $company = new Company();
        $company_tree = $company->generate_company_tree(0);
        // print_r($company_tree[0]);
        file_put_contents('result.json', json_encode($company_tree[0]));
        echo 'Total time: ' .  (microtime(true) - $start);
    }
}

(new TestScript())->execute();
