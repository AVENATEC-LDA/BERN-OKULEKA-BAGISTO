<?php

namespace Webkul\Suggestion\Http\Controllers\Shop;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Product\Repositories\ProductRepository;

class SuggestionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected ProductRepository $productRepository) {}

    /**
     * Handle the search results
     */
    public function search(): JsonResponse
    {
        $query = trim(request()->input('term'));

        if (! $query) {
            return new JsonResponse(['data' => []]);
        }

        $searchEngine = 'database';

        if (
            core()->getConfigData('catalog.products.search.engine') == 'elastic'
            && core()->getConfigData('catalog.products.search.storefront_mode') == 'elastic'
        ) {
            $searchEngine = 'elastic';
        }

        $limit = (int) (core()->getConfigData('suggestion.suggestion.general.products_limit') ?: 5);

        $products = $this->productRepository
            ->with(['images', 'categories'])
            ->setSearchEngine($searchEngine)
            ->getAll([
                'query' => $query,
                'category_id' => trim(request()->input('category')) ?: null,
                'channel_id' => core()->getCurrentChannel()->id,
                'status' => 1,
                'visible_individually' => 1,
                'sort' => 'name',
                'order' => 'asc',
            ]);

        $items = collect($products->items())->take($limit);

        return new JsonResponse(['data' => $items]);
    }
}
