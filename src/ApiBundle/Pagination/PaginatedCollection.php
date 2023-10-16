<?php

namespace ApiBundle\Pagination;

class PaginatedCollection
{
    private $items;
    private $total;
    private $count;
    private $page;
    private $_links = array();

    public function __construct(array $items, $page, $totalItems)
    {
        $this->items = $items;
        $this->total = $totalItems;
        $this->count = count($items);
        $this->page = $page;
    }

    public function addLink($ref, $url)
    {
        $this->_links[$ref] = $url;
    }
}