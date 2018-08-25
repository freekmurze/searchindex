<?php

namespace spec\Spatie\SearchIndex\SearchIndexHandlers;

use Elasticsearch\Client;
use PhpSpec\ObjectBehavior;
use Spatie\SearchIndex\Searchable;
use Elasticsearch\Namespaces\IndicesNamespace;

class ElasticsearchSpec extends ObjectBehavior
{
    protected $indexName;

    protected $searchableBody;
    protected $searchableType;
    protected $searchableId;

    protected $searchableObject;

    public function __construct()
    {
        $this->indexName = 'indexName';

        $this->searchableBody = ['body' => 'test'];
        $this->searchableType = 'product';
        $this->searchableId = 1;
    }

    public function let(Client $elasticsearch, Searchable $searchableObject)
    {
        $searchableObject->getSearchableBody()->willReturn($this->searchableBody);
        $searchableObject->getSearchableType()->willReturn($this->searchableType);
        $searchableObject->getSearchableId()->willReturn($this->searchableId);

        $this->beConstructedWith($elasticsearch);

        $this->setIndexName($this->indexName);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Spatie\SearchIndex\SearchIndexHandlers\Elasticsearch');
    }

    public function it_adds_a_searchable_object_to_the_search_index(Client $elasticsearch, Searchable $searchableObject)
    {
        $params = [];

        $params['body'][] = [
            'index' => [
                '_id'    => $this->searchableId,
                '_index' => $this->indexName,
                '_type'  => $this->searchableType,
            ],
        ];

        $params['body'][] = $this->searchableBody;

        $elasticsearch->bulk($params)->shouldBeCalled();

        $this->upsertToIndex($searchableObject);
    }

    public function it_adds_multiple_searchable_objects_to_the_search_index(Client $elasticsearch, Searchable $searchableObject)
    {
        $searchableObjects = [$searchableObject];
        $params = [];

        $params['body'][] = [
            'index' => [
                '_id'    => $this->searchableId,
                '_index' => $this->indexName,
                '_type'  => $this->searchableType,
            ],
        ];

        $params['body'][] = $this->searchableBody;

        $elasticsearch->bulk($params)->shouldBeCalled();

        $this->upsertToIndex($searchableObjects);
    }

    public function it_removes_a_searchable_object_from_the_index(Client $elasticsearch, Searchable $searchableObject)
    {
        $elasticsearch->delete(
            [
                'index' => $this->indexName,
                'type'  => $this->searchableType,
                'id'    => $this->searchableId,
            ]
        )->shouldBeCalled();

        $this->removeFromIndex($searchableObject);
    }

    public function it_an_object_from_the_index_by_type_and_id(Client $elasticsearch)
    {
        $elasticsearch->delete(
            [
                'index' => $this->indexName,
                'type'  => $this->searchableType,
                'id'    => $this->searchableId,
            ]
        )->shouldBeCalled();

        $this->removeFromIndexByTypeAndId($this->searchableType, $this->searchableId);
    }

    public function it_can_clear_the_index(Client $elasticsearch, IndicesNamespace $indices)
    {
        $elasticsearch->indices()->willReturn($indices);
        $indices->delete(['index' => $this->indexName])->shouldBeCalled();

        $this->clearIndex();
    }

    public function it_can_get_search_results(Client $elasticsearch)
    {
        $query = 'this is a testquery';

        $elasticsearch->search($query)->shouldBeCalled();

        $this->getResults($query);
    }
}
