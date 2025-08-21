<?php
/**
 * Search Helper Functions for Handicrafts Marketplace
 */

/**
 * Build advanced search conditions with fuzzy matching
 */
function buildAdvancedSearchConditions($search) {
    $search = trim($search);
    $conditions = [];
    $params = [];
    
    // Clean and prepare search terms
    $searchTerm = "%$search%";
    $searchWords = explode(' ', $search);
    
    // Basic LIKE search (case insensitive)
    $basicConditions = [
        'LOWER(p.name) LIKE LOWER(?)',
        'LOWER(p.description) LIKE LOWER(?)',
        'LOWER(p.short_description) LIKE LOWER(?)',
        'LOWER(c.name) LIKE LOWER(?)',
        'LOWER(a.name) LIKE LOWER(?)'
    ];
    
    foreach ($basicConditions as $condition) {
        $conditions[] = $condition;
        $params[] = $searchTerm;
    }
    
    // Individual word search for partial matches
    foreach ($searchWords as $word) {
        $word = trim($word);
        if (strlen($word) >= 3) { // Only search words with 3+ characters
            $wordTerm = "%$word%";
            $wordConditions = [
                'LOWER(p.name) LIKE LOWER(?)',
                'LOWER(p.description) LIKE LOWER(?)',
                'LOWER(p.short_description) LIKE LOWER(?)',
                'LOWER(c.name) LIKE LOWER(?)',
                'LOWER(a.name) LIKE LOWER(?)'
            ];
            
            foreach ($wordConditions as $condition) {
                $conditions[] = $condition;
                $params[] = $wordTerm;
            }
        }
    }
    
    // Advanced phonetic matching for similar-sounding words (simplified for better performance)
    if (function_exists('soundex') && function_exists('metaphone') && count($searchWords) < 5) {
        // SOUNDEX matching (basic phonetic similarity)
        $soundexConditions = [
            'SOUNDEX(p.name) = SOUNDEX(?)',
            'SOUNDEX(c.name) = SOUNDEX(?)',
            'SOUNDEX(a.name) = SOUNDEX(?)',
            'SOUNDEX(p.description) = SOUNDEX(?)',
            'SOUNDEX(p.short_description) = SOUNDEX(?)'
        ];
        
        foreach ($soundexConditions as $condition) {
            $conditions[] = $condition;
            $params[] = $search;
        }
        
        // Double Metaphone for more accurate phonetic matching
        $metaphoneKey = metaphone($search);
        $metaphoneConditions = [
            'SUBSTRING(p.name, 1, ' . strlen($metaphoneKey) . ') SOUNDS LIKE ?',
            'SUBSTRING(c.name, 1, ' . strlen($metaphoneKey) . ') SOUNDS LIKE ?',
            'SUBSTRING(a.name, 1, ' . strlen($metaphoneKey) . ') SOUNDS LIKE ?'
        ];
        
        foreach ($metaphoneConditions as $condition) {
            $conditions[] = $condition;
            $params[] = $metaphoneKey;
        }
        
        // Handle individual words for compound searches (simplified)
        foreach ($searchWords as $word) {
            $word = trim($word);
            if (strlen($word) >= 3 && count($searchWords) <= 3) {
                // SOUNDEX for each word (limited fields)
                foreach (['p.name', 'c.name', 'a.name'] as $field) {
                    $conditions[] = "SOUNDEX($field) = SOUNDEX(?)";
                    $params[] = $word;
                }
            }
        }
        
        // Add common sound-alike patterns (limited for performance)
        $soundPatterns = array_slice(getSoundAlikePatterns($search), 0, 5); // Limit to 5 patterns
        foreach ($soundPatterns as $pattern) {
            $conditions[] = "(LOWER(p.name) LIKE ? OR LOWER(p.short_description) LIKE ?)";
            $params = array_merge($params, ["%$pattern%", "%$pattern%"]);
        }
    }
    
    // Levenshtein distance for fuzzy matching (if available)
    if (function_exists('levenshtein')) {
        // We'll implement this in PHP after getting results
        // MySQL doesn't have built-in Levenshtein
    }
    
    // Get synonyms and common misspellings
    $synonyms = getSynonymsAndMisspellings($search);
    foreach ($synonyms as $synonym) {
        $synonymTerm = "%$synonym%";
        $synonymConditions = [
            'LOWER(p.name) LIKE LOWER(?)',
            'LOWER(p.description) LIKE LOWER(?)',
            'LOWER(p.short_description) LIKE LOWER(?)'
        ];
        
        foreach ($synonymConditions as $condition) {
            $conditions[] = $condition;
            $params[] = $synonymTerm;
        }
    }
    
    return [
        'conditions' => $conditions,
        'params' => $params
    ];
}

/**
 * Get synonyms and common misspellings for search terms
 */
function getSynonymsAndMisspellings($search) {
    $search = strtolower(trim($search));
    $synonyms = [];
    
    // Common handicraft terms and their variations
    $synonymMap = [
        // Pottery variations
        'pottery' => ['ceramic', 'clay', 'terracotta', 'earthenware'],
        'ceramic' => ['pottery', 'clay', 'terracotta'],
        'clay' => ['pottery', 'ceramic', 'terracotta'],
        'pot' => ['pottery', 'ceramic', 'vessel'],
        'vase' => ['pottery', 'ceramic', 'pot', 'vessel'],
        
        // Embroidery variations
        'embroidery' => ['needlework', 'stitching', 'threadwork', 'handwork'],
        'embroidered' => ['embroidery', 'stitched', 'needlework'],
        'stitch' => ['embroidery', 'needlework', 'sewing'],
        'thread' => ['embroidery', 'needlework', 'yarn'],
        
        // Textile variations
        'textile' => ['fabric', 'cloth', 'material', 'weaving'],
        'fabric' => ['textile', 'cloth', 'material'],
        'cloth' => ['textile', 'fabric', 'material'],
        'weaving' => ['textile', 'fabric', 'handloom'],
        'handloom' => ['weaving', 'textile', 'fabric'],
        
        // Jute variations
        'jute' => ['fiber', 'natural', 'eco', 'sustainable'],
        'fiber' => ['jute', 'natural', 'textile'],
        
        // Metal craft variations
        'metal' => ['brass', 'copper', 'bronze', 'iron', 'steel'],
        'brass' => ['metal', 'bronze', 'copper'],
        'copper' => ['metal', 'brass', 'bronze'],
        'bronze' => ['metal', 'brass', 'copper'],
        
        // Wood craft variations
        'wood' => ['wooden', 'timber', 'carved', 'handicraft'],
        'wooden' => ['wood', 'timber', 'carved'],
        'carved' => ['wood', 'wooden', 'sculpture'],
        'carving' => ['carved', 'wood', 'sculpture'],
        
        // Common misspellings
        'handicraft' => ['handcraft', 'handicrafts', 'handmade'],
        'handmade' => ['handicraft', 'handcraft', 'artisan'],
        'artisan' => ['craftsman', 'handmade', 'handicraft'],
        'traditional' => ['classic', 'heritage', 'cultural'],
        
        // Bengali/Local terms
        'kantha' => ['embroidery', 'quilting', 'stitching'],
        'dhokra' => ['metal', 'brass', 'bronze', 'casting'],
        'terracotta' => ['pottery', 'clay', 'ceramic'],
        'jamdani' => ['textile', 'weaving', 'fabric', 'muslin'],
        'nakshi' => ['embroidery', 'decorative', 'pattern']
    ];
    
    // Common typing mistakes and phonetic variations
    $misspellings = [
        'handicraft' => ['handicraf', 'handicrafts', 'handycraft', 'handiraft'],
        'embroidery' => ['embroidry', 'embroydery', 'embrodery', 'emroidery'],
        'pottery' => ['potery', 'poterry', 'potary'],
        'ceramic' => ['ceremic', 'ceramik', 'serramic'],
        'traditional' => ['tradicional', 'tradisional', 'traditional'],
        'artisan' => ['artizen', 'artisian', 'artisan'],
        'handmade' => ['handmaid', 'hand-made', 'handmde'],
        'textile' => ['textil', 'textille', 'textie'],
        'wooden' => ['wooded', 'woden', 'woodn'],
        'metal' => ['metall', 'meatl', 'metel'],
        'jute' => ['jut', 'juite', 'joot'],
        'weaving' => ['weving', 'weavng', 'weaving']
    ];
    
    // Check for direct matches in synonym map
    if (isset($synonymMap[$search])) {
        $synonyms = array_merge($synonyms, $synonymMap[$search]);
    }
    
    // Check for misspellings
    if (isset($misspellings[$search])) {
        $synonyms = array_merge($synonyms, $misspellings[$search]);
    }
    
    // Check if search might be a misspelling of a known term
    foreach ($misspellings as $correct => $variations) {
        if (in_array($search, $variations)) {
            $synonyms[] = $correct;
            if (isset($synonymMap[$correct])) {
                $synonyms = array_merge($synonyms, $synonymMap[$correct]);
            }
        }
    }
    
    // Check individual words in the search
    $words = explode(' ', $search);
    foreach ($words as $word) {
        $word = trim(strtolower($word));
        if (strlen($word) >= 3) {
            if (isset($synonymMap[$word])) {
                $synonyms = array_merge($synonyms, $synonymMap[$word]);
            }
            if (isset($misspellings[$word])) {
                $synonyms = array_merge($synonyms, $misspellings[$word]);
            }
        }
    }
    
    // If Levenshtein is available, find close matches
    if (function_exists('levenshtein')) {
        $allTerms = array_merge(
            array_keys($synonymMap),
            array_keys($misspellings)
        );
        
        foreach ($words as $word) {
            if (strlen($word) >= 3) {
                foreach ($allTerms as $term) {
                    $distance = levenshtein($word, $term);
                    $maxDistance = strlen($term) > 4 ? 2 : 1;
                    
                    if ($distance <= $maxDistance) {
                        if (isset($synonymMap[$term])) {
                            $synonyms = array_merge($synonyms, $synonymMap[$term]);
                        }
                        $synonyms[] = $term;
                    }
                }
            }
        }
    }
    
    return array_unique($synonyms);
}

/**
 * Get common sound-alike patterns for search terms
 */
function getSoundAlikePatterns($search) {
    $patterns = [];
    $search = strtolower($search);
    
    // Common sound-alike patterns in English and Bengali terms
    $soundPatterns = [
        // Vowel sounds
        'a' => ['e', 'u'],
        'e' => ['i', 'a'],
        'i' => ['e', 'ee'],
        'o' => ['u', 'oo'],
        'u' => ['oo', 'o'],
        
        // Consonant sounds
        'f' => ['ph', 'ff'],
        'ph' => ['f'],
        'c' => ['k', 's'],
        'k' => ['c', 'q'],
        'ck' => ['k', 'q'],
        's' => ['c', 'ss'],
        't' => ['tt', 'd'],
        'd' => ['t', 'dd'],
        'th' => ['t', 'd'],
        'v' => ['b', 'bh'],
        'w' => ['v'],
        'y' => ['i', 'ee'],
        
        // Bengali specific patterns
        'sh' => ['s', 'ss'],
        'ch' => ['c', 'ts'],
        'j' => ['z', 'jh'],
        'tr' => ['t'],
        'dr' => ['d'],
        
        // Double consonants
        'tt' => ['t'],
        'dd' => ['d'],
        'nn' => ['n'],
        'ss' => ['s'],
        'll' => ['l'],
        'rr' => ['r'],
        
        // Common endings
        'er' => ['ar', 'or'],
        'or' => ['er', 'ar'],
        'ar' => ['er', 'or'],
        'ry' => ['ri', 'ree'],
        'ing' => ['in', 'een'],
        
        // Craft-specific patterns
        'craft' => ['kraft', 'craff'],
        'hand' => ['hund', 'hend'],
        'wood' => ['vood', 'ud'],
        'metal' => ['metl', 'mettle'],
        'clay' => ['klay', 'kley'],
        'silk' => ['silq', 'silc'],
        'jute' => ['joot', 'jutt'],
        'weave' => ['veev', 'weev'],
        
        // Bengali craft terms
        'kantha' => ['kanta', 'kuntha'],
        'nakshi' => ['nokshi', 'naksi'],
        'jamdani' => ['jamdoni', 'jamdanee'],
        'dhokra' => ['dokra', 'dhokora'],
        'terracotta' => ['teracota', 'terrakota']
    ];
    
    // Generate patterns based on the search term
    foreach ($soundPatterns as $original => $variations) {
        if (strpos($search, $original) !== false) {
            foreach ($variations as $variant) {
                $patterns[] = str_replace($original, $variant, $search);
            }
        }
    }
    
    // Handle compound words
    $words = explode(' ', $search);
    foreach ($words as $word) {
        if (strlen($word) >= 3) {
            foreach ($soundPatterns as $original => $variations) {
                if (strpos($word, $original) !== false) {
                    foreach ($variations as $variant) {
                        $newWord = str_replace($original, $variant, $word);
                        if ($newWord !== $word) {
                            // Replace this word in the full search term
                            $patterns[] = str_replace($word, $newWord, $search);
                        }
                    }
                }
            }
        }
    }
    
    // Add common phonetic variations for craft-specific terms
    $craftVariations = [
        // Common variations in craft terminology
        ['embroidery', 'embroidry', 'embrodery', 'embroydery'],
        ['ceramic', 'seramic', 'ceramik', 'seramik'],
        ['pottery', 'potery', 'potry', 'pottry'],
        ['textile', 'textil', 'textyle', 'texstyle'],
        ['weaving', 'weving', 'weeving', 'veaving'],
        ['handicraft', 'handycraft', 'handikraft', 'handycraft'],
        ['traditional', 'tradisional', 'tradishanal', 'tradishonal'],
        
        // Bengali craft term variations
        ['kantha', 'kanta', 'kantha', 'kuntha'],
        ['nakshi', 'nakshi', 'nokshi', 'naqshi'],
        ['jamdani', 'jamdoni', 'jamdanee', 'jamdoney'],
        ['dhokra', 'dokra', 'dhokora', 'dokora'],
        ['terracotta', 'teracota', 'terakota', 'terrakota']
    ];
    
    foreach ($craftVariations as $variations) {
        if (in_array($search, $variations)) {
            $patterns = array_merge($patterns, array_diff($variations, [$search]));
        }
    }
    
    // Add common pronunciation-based variations
    $pronunciationPatterns = [
        // Vowel sound variations
        ['a', 'ah', 'ar'],
        ['e', 'ee', 'ea'],
        ['i', 'ee', 'ea'],
        ['o', 'oh', 'ow'],
        ['u', 'oo', 'ou'],
        
        // Consonant sound variations
        ['f', 'ph', 'ff'],
        ['k', 'c', 'ch'],
        ['s', 'c', 'ss'],
        ['t', 'tt', 'th'],
        ['sh', 'ch', 'ss'],
        
        // Bengali-specific sound variations
        ['sh', 's', 'ss'],
        ['ch', 'c', 'ts'],
        ['j', 'z', 'jh'],
        ['v', 'b', 'bh'],
        ['w', 'v', 'u']
    ];
    
    foreach ($pronunciationPatterns as $sounds) {
        foreach ($sounds as $sound) {
            if (strpos($search, $sound) !== false) {
                foreach ($sounds as $variant) {
                    if ($variant !== $sound) {
                        $patterns[] = str_replace($sound, $variant, $search);
                    }
                }
            }
        }
    }
    
    // Remove duplicates and empty patterns
    $patterns = array_filter(array_unique($patterns));
    
    return $patterns;
}
?>
