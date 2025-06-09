<?php

/**
 * Core Framework - SearchModel
 *
 * @license    MIT (https://mit-license.org/)
 * @author     Louis Ouellet <louis@laswitchtech.com>
 */

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Abstracts\Model;

class SearchModel extends Model {

    /**
     * Save a search index
     *
     * @param string $title The title of the index
     * @param string $route The route to index
     * @param string $segments The segments to index
     * @param string $locale The locale to index
     * @param string $content The content to index
     * @return int affected rows
     */
    public function save(string $title, string $route, int $isPublic, string $segments, string $locale, string $origin, string $content): int
    {
        // Import Global Variables
        global $AUTH, $CONFIG;

        // Create a new Query
        $Query = $this->Database->query()
            ->table("indexes")
            ->select('*')
            ->where('id', 9999, '<>')
            ->where('owner', $AUTH->isAuthenticated() ? $AUTH->user()->id : $CONFIG->get('database','username'))
            ->where('route', $route)
            ->where('isPublic', $isPublic)
            ->where('segments', $segments)
            ->where('locale', $locale);

        // Retrieve the Results
        $result = $Query->result();

        // Create a new Query
        $Query = $this->Database->query()->table("indexes");

        // Setup the data
        $data = [
            'owner' => $AUTH->isAuthenticated() ? $AUTH->user()->id : $CONFIG->get('database','username'),
            'title' => $title,
            'route' => $route,
            'isPublic' => $isPublic,
            'segments' => $segments,
            'locale' => $locale,
            'origin' => $origin,
            'content' => $content,
            'searchable' => strtoupper($content),
        ];

        // Check if an index already exists
        if(count($result)){

            // Configure the Query
            $Query->update($data)->where('id', $result[0]['id']);
        } else {

            // Configure the Query
            $Query->insert($data);
        }

        // Execute the Query
        $affectedRows = $Query->execute();

        return $affectedRows;
    }

    /**
     * Find a search index
     *
     * @param string $string The string to search
     * @return array The result of the search
     */
    public function find(string $string, bool $onlyPublic = false): array
    {
        // Create a new Query
        $Query = $this->Database->query()
            ->table("indexes")
            ->select('*')
            ->filter()
            ->where('id', 9999, '<>')
            ->filter()
            ->where('searchable', '%' . strtoupper($string) . '%', 'LIKE', 'OR')
            ->where('title', '%' . strtoupper($string) . '%', 'LIKE', 'OR');

        // Check if the user is authenticated
        if($onlyPublic){
            $Query->filter()->where('isPublic', 1);
        }

        // Retrieve the Results
        return $Query->result();
    }

    /**
     * Generate an excerpt of the content
     */
    public function excerpt(string $query, string $content): string
    {
        $pos = stripos($content, $query);
        $start = max(0, $pos - 50);
        return '…'.mb_substr($content, $start, 400).'…';
    }

    /**
     * Score the search index
     */
    public function score(string $query, array $idx): int
    {
        global $LOCALE;

        $q  = $query;                     // keep original case
        $qs = mb_strtolower($query);      // lower‑case once
        $h  = mb_strtolower($idx['origin']);
        $c  = mb_strtolower($idx['content']);
        $e  = mb_strtolower($idx['excerpt']);
        $t  = mb_strtolower($idx['title']);
        $u  = mb_strtolower($idx['route']['route']);
        $s  = mb_strtolower($idx['segments']);

        $score  = 0;

        /* ---------- structure weights ---------- */
        $score += (stripos($u, $q) !== false) ? 10 : 0;
        $score += (stripos($t, $q) !== false) ?  8 : 0;
        $score += (stripos($s, $q) !== false) ?  1 : 0;

        /* ---------- heading weights (per hit) ---------- */
        $score += substr_count($h, '<h1') * 6 * substr_count($h, $qs);
        $score += substr_count($h, '<h2') * 4 * substr_count($h, $qs);
        $score += substr_count($h, '<h3') * 2 * substr_count($h, $qs);

        /* ---------- body occurrences ---------- */
        $score += substr_count($c, $qs);          // +1 per hit

        /* ---------- excerpt occurrences ---------- */
        $score += substr_count($e, $qs) * 5;          // +1 per hit

        /* ---------- locale bonus ---------- */
        [$lang,$region] = explode('-', mb_strtolower($idx['locale']).'-');
        [$cl,  $cr]    = explode('-', mb_strtolower($LOCALE->current()).'-');
        if ($lang === $cl && $region === $cr)      $score += 3; // exact match
        elseif ($lang === $cl)                     $score += 1; // same language

        /* ---------- recency bonus (optional) ---------- */
        $age = (time() - strtotime($idx['modified'])) / 86400;  // days
        $score += max(0, 3 - floor($age / 30));  // +3 (≤30 d), +2 (≤60 d)…0

        /* ---------- cap & return ---------- */
        return (int) $score;
    }
}
