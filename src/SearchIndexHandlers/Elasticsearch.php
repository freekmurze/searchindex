<?php

namespace Spatie\SearchIndex\SearchIndexHandlers;

use Elasticsearch\Client;
use Spatie\SearchIndex\Searchable;
use Spatie\SearchIndex\SearchIndexHandler;

class Elasticsearch implements SearchIndexHandler
{
    /**
     * @var Elasticsearch
     */
    protected $elasticsearch;

    /**
     * @var string
     */
    protected $indexName;

    /**
     * @param Client $elasticsearch
     */
    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Set the name of the index that should be used by default.
     *
     * @param $indexName
     *
     * @return $this
     */
    public function setIndexName($indexName)
    {
        $this->indexName = $indexName;

        return $this;
    }

    /**
     * Add or update the given searchable subject or array of subjects or Traversable object containing subjects.
     *
     * @param Searchable|array|Traversable $subject
     */
    public function upsertToIndex($subject)
    {
        if ($subject instanceof Searchable) {
            $this->elasticsearch->index(
                [
                    'index' => $this->indexName,
                    'type' => $subject->getSearchableType(),
                    'id' => $subject->getSearchableId(),
                    'body' => $subject->getSearchableBody(),
                ]
            );
        } elseif (is_array($subject) || $subject instanceof \Traversable) {
            $params = [];

            foreach ($subject as $item) {
                if (! $item instanceof Searchable) {
                    throw new \InvalidArgumentException;
                }

                $params['body'][] = [
                    "index" => [
                        '_id' => $item->getSearchableId(),
                        '_index' => $this->indexName,
                        '_type' => $item->getSearchableType(),
                    ]
                ];

                $params['body'][] = $item->getSearchableBody();
            }

            $this->elasticsearch->bulk($params);
        } else {
            throw new \InvalidArgumentException('Subject must be a searchable or array of searchables');
        }
    }

    /**
     * Remove the given subject from the search index.
     *
     * @param Searchable $subject
     */
    public function removeFromIndex(Searchable $subject)
    {
        $this->elasticsearch->delete(
            [
                'index' => $this->indexName,
                'type' => $subject->getSearchableType(),
                'id' => $subject->getSearchableId(),
            ]
        );
    }

    /**
     * Remove an item from the search index by type and id.
     *
     * @param string $type
     * @param int    $id
     */
    public function removeFromIndexByTypeAndId($type, $id)
    {
        $this->elasticsearch->delete(
            [
                'index' => $this->indexName,
                'type' => $type,
                'id' => $id,
            ]
        );
    }

    /**
     * Remove everything from the index.
     *
     * @return mixed
     */
    public function clearIndex()
    {
        $this->elasticsearch->indices()->delete(['index' => $this->indexName]);
    }

    /**
     * Get the results for the given query.
     *
     * @param array $query
     *
     * @return mixed
     */
    public function getResults($query)
    {
        return $this->elasticsearch->search($query);
    }

    /**
     * Get the underlying client.
     *
     * @return Elasticsearch
     */
    public function getClient()
    {
        return $this->elasticsearch;
    }
}
