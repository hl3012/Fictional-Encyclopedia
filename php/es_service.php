<?php
// === ElasticSearch===
require_once __DIR__ . '/vendor/autoload.php';
class ElasticSearchService {
    public $client;
    private $indexName = 'fiction_entries';
    
    public function __construct() {
        try {
            
            if (class_exists('Elastic\Elasticsearch\ClientBuilder')) {
                $this->client = Elastic\Elasticsearch\ClientBuilder::create()
                    ->setHosts(['elasticsearch:9200'])
                    ->build();
            } 
     
            else if (class_exists('Elasticsearch\ClientBuilder')) {
                $this->client = Elasticsearch\ClientBuilder::create()
                    ->setHosts(['elasticsearch:9200'])
                    ->build();
            } else {
                $this->client = null;
                error_log("ElasticSearch client class not found");
            }
            
            if ($this->client) {
                $this->createIndex();
            }
        } catch (Exception $e) {
            $this->client = null;
            error_log("ES connection failed: " . $e->getMessage());
        }
    }
    
    // Create index (inverted index)
    private function createIndex() {
        if (!$this->client) return;
        
        try {
            $params = [
                'index' => $this->indexName,
                'body' => [
                    'mappings' => [
                        'properties' => [
                            'eid' => ['type' => 'integer'],
                            'name' => ['type' => 'text'],   
                            'content' => ['type' => 'text'],  
                            'categories' => ['type' => 'text']
                        ]
                    ]
                ]
            ];
            $this->client->indices()->create($params);
        } catch (Exception $e) {
            // already exists
        }
    }
    
    // Search entry (inverted index)
    public function search($query) {
        if (!$this->client) return [];
        
        try {
            $params = [
                'index' => $this->indexName,
                'body' => [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['name', 'content']  
                        ]
                    ]
                ]
            ];
            
            $response = $this->client->search($params);
            return $this->formatResults($response);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Add entry to inverted index
    public function indexEntry($entryData) {
        if (!$this->client) return false;
        
        try {
            $params = [
                'index' => $this->indexName,
                'id' => $entryData['eid'],
                'body' => $entryData
            ];
            $this->client->index($params);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function formatResults($response) {
        $results = [];
        if (isset($response['hits']['hits'])) {
            foreach ($response['hits']['hits'] as $hit) {
                $results[] = [
                    'eid' => $hit['_source']['eid'],
                    'name' => $hit['_source']['name'],
                    'content' => $hit['_source']['content'],
                    'score' => $hit['_score']  
                ];
            }
        }
        return $results;
    }


    public function deleteEntry($eid) {
        if (!$this->client) return false;
        
        try {
            $params = [
                'index' => $this->indexName,
                'id'    => $eid
            ];
            $this->client->delete($params);
            return true;
        } catch (Exception $e) {
            error_log("ES delete entry failed: " . $e->getMessage());
            return false;
        }
    }
}
?>