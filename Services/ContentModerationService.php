<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class ContentModerationService
{
    /**
     * Check content for moderation.
     *
     * @param string $content
     * @return array
     */

    // public function checkContent(string $content): array
    // {
    //     try {
    //         $response = OpenAI::moderations()->create([
    //             'model' => 'omni-moderation-latest',
    //             'input' => $content,
    //         ]);

    //         $result = $response['results'][0];

    //         $flaggedCategories = [];
    //         $maxScore = 0;

    //         // categories is now an associative array
    //         foreach ($result['categories'] as $category => $isViolated) {
    //             if ($isViolated === true) {
    //                 $flaggedCategories[] = $category;
    //             }
    //         }

    //         // category_scores is also associative
    //         foreach ($result['category_scores'] as $category => $score) {
    //             if ($score > $maxScore) {
    //                 $maxScore = $score;
    //             }
    //         }

    //         return [
    //             'flagged' => $result['flagged'],
    //             'categories' => $flaggedCategories,
    //             'score' => round($maxScore, 3),
    //         ];

    //     } catch (\Throwable $e) {
    //         \Log::error('Content Moderation Error: ' . $e->getMessage());

    //         return [
    //             'flagged' => false,
    //             'categories' => [],
    //             'score' => 0.0,
    //             'error' => $e->getMessage(),
    //         ];
    //     }
    // }

    public function checkContent(string $content): array
{
    try {

        // Offensive words list
        $badWords = [
            // German (General Swearwords, Insults & Slurs)
            'fick', 'ficken', 'fickt', 'ficker', 'arsch', 'arschloch', 'hure', 'hurensohn', 
            'schlampe', 'fotze', 'wichser', 'scheiße', 'scheisse', 'kacke', 'miststück', 'depp',
            'pimmel', 'vagina', 'nutte', 'wixe', 'idiot', 'bastard', 'schwachkopf', 'vollidiot',
            'wixer', 'schweinehund', 'sau', 'mistkerl', 'pisser', 'scheiss', 'esel', 'kamel',
            
            // German Bullying, Harassment & Animal slurs
            'du esel', 'du kuckuck', 'du pfeife', 'du hornochse', 'du affe', 'du schwein',
            
            // English (Swearwords & Common Slurs)
            'fuck', 'fucking', 'fucker', 'asshole', 'bastard', 'idiot', 'nigger', 'nazi', 
            'hate', 'kill', 'murder', 'racist', 'sexist', 'slut', 'whore', 'bitch', 'cunt',
            'pussy', 'dick', 'cock', 'wanker', 'motherfucker', 'prick', 'dumbass', 'moron',
            
            // English Bullying, Harassment & Animal slurs
            'donkey', 'monkey', 'pig', 'ape', 'coward', 'loser', 'jerk', 'fatty',
            'you donkey', 'you monkey', 'you pig', 'you ape', 'you loser', 'you idiot'
        ];

        $contentLower = mb_strtolower($content, 'UTF-8');
        $flaggedWords = [];

        foreach ($badWords as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/ui', $contentLower)) {
                $flaggedWords[] = $word;
            }
        }

        // If direct offensive word found
        if (!empty($flaggedWords)) {
            return [
                'flagged' => true,
                'categories' => ['offensive_language'],
                'score' => 1.0,
                'matched_words' => $flaggedWords
            ];
        }

        // If no word match → run OpenAI moderation
        $response = OpenAI::moderations()->create([
            'model' => 'omni-moderation-latest',
            'input' => $content,
        ]);

        $result = $response['results'][0];

        $flaggedCategories = [];
        $maxScore = 0;

        foreach ($result['categories'] as $category => $isViolated) {
            if ($isViolated === true) {
                $flaggedCategories[] = $category;
            }
        }

        foreach ($result['category_scores'] as $category => $score) {
            if ($score > $maxScore) {
                $maxScore = $score;
            }
        }

        return [
            'flagged' => $result['flagged'],
            'categories' => $flaggedCategories,
            'score' => round($maxScore, 3),
        ];

    } catch (\Throwable $e) {

        \Log::error('Content Moderation Error: ' . $e->getMessage());

        return [
            'flagged' => false,
            'categories' => [],
            'score' => 0.0,
            'error' => $e->getMessage(),
        ];
    }
}
}
