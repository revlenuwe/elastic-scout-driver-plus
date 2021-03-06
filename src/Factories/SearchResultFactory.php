<?php declare(strict_types=1);

namespace ElasticScoutDriverPlus\Factories;

use ElasticAdapter\Search\Hit;
use ElasticAdapter\Search\SearchResponse;
use ElasticScoutDriverPlus\Match;
use ElasticScoutDriverPlus\SearchResult;
use ElasticScoutDriverPlus\Support\ModelScope;
use Illuminate\Support\Collection;

final class SearchResultFactory
{
    public static function makeFromSearchResponseUsingModelScope(
        SearchResponse $searchResponse,
        ModelScope $modelScope
    ): SearchResult {
        $matches = self::makeMatches(
            $searchResponse->getHits(),
            new LazyModelFactory($searchResponse, $modelScope)
        );

        $suggestions = self::makeSuggestions($searchResponse->getSuggestions());
        $aggregations = self::makeAggregations($searchResponse->getAggregations());

        return new SearchResult($matches, $suggestions, $aggregations, $searchResponse->getHitsTotal());
    }

    private static function makeMatches(array $hits, LazyModelFactory $lazyModelFactory): Collection
    {
        return collect($hits)->map(static function (Hit $hit) use ($lazyModelFactory) {
            return new Match(
                $lazyModelFactory,
                $hit->getIndexName(),
                $hit->getDocument(),
                $hit->getHighlight(),
                $hit->getScore()
            );
        });
    }

    private static function makeSuggestions(array $suggestions): Collection
    {
        return collect($suggestions)->mapWithKeys(static function (array $entries, string $suggestion) {
            return [$suggestion => collect($entries)];
        });
    }

    private static function makeAggregations(array $aggregations): Collection
    {
        return collect($aggregations);
    }
}
