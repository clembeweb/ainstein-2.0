<?php

namespace App\Services\Tools;

use App\Models\AdvCampaign;
use App\Models\AdvGeneratedAsset;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Campaign Assets Generator Service
 *
 * Generates optimized Google Ads assets (titles, descriptions) using OpenAI
 * Supports RSA (Responsive Search Ads) and PMAX (Performance Max) campaigns
 */
class CampaignAssetsGenerator
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Generate assets for a campaign based on its type
     *
     * @param AdvCampaign $campaign
     * @return AdvGeneratedAsset
     * @throws Exception
     */
    public function generate(AdvCampaign $campaign): AdvGeneratedAsset
    {
        if ($campaign->type === 'rsa') {
            return $this->generateRSAAssets($campaign);
        } elseif ($campaign->type === 'pmax') {
            return $this->generatePMaxAssets($campaign);
        }

        throw new Exception("Unknown campaign type: {$campaign->type}");
    }

    /**
     * Generate RSA (Responsive Search Ads) assets
     *
     * RSA Requirements:
     * - 3-15 titles (max 30 chars each)
     * - 2-4 descriptions (max 90 chars each)
     *
     * @param AdvCampaign $campaign
     * @return AdvGeneratedAsset
     * @throws Exception
     */
    public function generateRSAAssets(AdvCampaign $campaign): AdvGeneratedAsset
    {
        Log::info('Generating RSA assets', ['campaign_id' => $campaign->id]);

        // Build prompt
        $prompt = $this->buildRSAPrompt($campaign);

        // Call OpenAI with JSON mode
        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt('rsa', $campaign->language)
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        $result = $this->openAIService->parseJSON($messages, null, [
            'use_case' => 'campaigns',
            'max_tokens' => 2000,
            'temperature' => 0.8,
        ]);

        if (!$result['success']) {
            throw new Exception('Failed to generate RSA assets: ' . ($result['error'] ?? 'Unknown error'));
        }

        $parsed = $result['parsed'];

        // Validate and extract assets
        $titles = $this->validateTitles($parsed['titles'] ?? [], 30, 3, 15, 'RSA');
        $descriptions = $this->validateDescriptions($parsed['descriptions'] ?? [], 90, 2, 4, 'RSA');

        // Calculate quality score
        $qualityScore = $this->calculateQualityScore([
            'titles' => $titles,
            'descriptions' => $descriptions,
        ], $campaign);

        // Update campaign tokens
        $campaign->update([
            'tokens_used' => $result['tokens_used'],
            'model_used' => $result['model'],
        ]);

        // Track tokens for tenant
        $this->openAIService->trackTokenUsage(
            $campaign->tenant_id,
            $result['tokens_used'],
            $result['model'],
            'campaign_rsa',
            ['campaign_id' => $campaign->id]
        );

        // Create asset
        $asset = $campaign->assets()->create([
            'type' => 'rsa',
            'titles' => $titles,
            'descriptions' => $descriptions,
            'ai_quality_score' => $qualityScore,
        ]);

        Log::info('RSA assets generated', [
            'campaign_id' => $campaign->id,
            'asset_id' => $asset->id,
            'titles_count' => count($titles),
            'descriptions_count' => count($descriptions),
            'quality_score' => $qualityScore,
            'tokens_used' => $result['tokens_used'],
        ]);

        return $asset;
    }

    /**
     * Generate PMAX (Performance Max) assets
     *
     * PMAX Requirements:
     * - 3-5 short titles (max 30 chars)
     * - 1-5 long titles (max 90 chars)
     * - 1-5 descriptions (max 90 chars)
     *
     * @param AdvCampaign $campaign
     * @return AdvGeneratedAsset
     * @throws Exception
     */
    public function generatePMaxAssets(AdvCampaign $campaign): AdvGeneratedAsset
    {
        Log::info('Generating PMAX assets', ['campaign_id' => $campaign->id]);

        // Build prompt
        $prompt = $this->buildPMaxPrompt($campaign);

        // Call OpenAI with JSON mode
        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt('pmax', $campaign->language)
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        $result = $this->openAIService->parseJSON($messages, null, [
            'use_case' => 'campaigns',
            'max_tokens' => 2000,
            'temperature' => 0.8,
        ]);

        if (!$result['success']) {
            throw new Exception('Failed to generate PMAX assets: ' . ($result['error'] ?? 'Unknown error'));
        }

        $parsed = $result['parsed'];

        // Validate and extract assets
        $titles = $this->validateTitles($parsed['short_titles'] ?? [], 30, 3, 5, 'PMAX short');
        $longTitles = $this->validateTitles($parsed['long_titles'] ?? [], 90, 1, 5, 'PMAX long');
        $descriptions = $this->validateDescriptions($parsed['descriptions'] ?? [], 90, 1, 5, 'PMAX');

        // Calculate quality score
        $qualityScore = $this->calculateQualityScore([
            'titles' => $titles,
            'long_titles' => $longTitles,
            'descriptions' => $descriptions,
        ], $campaign);

        // Update campaign tokens
        $campaign->update([
            'tokens_used' => $result['tokens_used'],
            'model_used' => $result['model'],
        ]);

        // Track tokens for tenant
        $this->openAIService->trackTokenUsage(
            $campaign->tenant_id,
            $result['tokens_used'],
            $result['model'],
            'campaign_pmax',
            ['campaign_id' => $campaign->id]
        );

        // Create asset
        $asset = $campaign->assets()->create([
            'type' => 'pmax',
            'titles' => $titles,
            'long_titles' => $longTitles,
            'descriptions' => $descriptions,
            'ai_quality_score' => $qualityScore,
        ]);

        Log::info('PMAX assets generated', [
            'campaign_id' => $campaign->id,
            'asset_id' => $asset->id,
            'short_titles_count' => count($titles),
            'long_titles_count' => count($longTitles),
            'descriptions_count' => count($descriptions),
            'quality_score' => $qualityScore,
            'tokens_used' => $result['tokens_used'],
        ]);

        return $asset;
    }

    /**
     * Build RSA generation prompt
     */
    protected function buildRSAPrompt(AdvCampaign $campaign): string
    {
        return <<<PROMPT
Generate Google Ads RSA (Responsive Search Ads) assets for the following campaign:

Campaign Name: {$campaign->name}
Campaign Briefing: {$campaign->info}
Keywords: {$campaign->keywords}
Destination URL: {$campaign->url}
Language: {$campaign->language}

Requirements:
- Generate 15 unique titles (max 30 characters each)
- Generate 4 unique descriptions (max 90 characters each)
- Include keywords naturally in the copy
- Use clear calls-to-action
- Vary the messaging to test different angles
- Focus on benefits and value propositions

Return JSON format:
{
  "titles": ["title1", "title2", ...],
  "descriptions": ["desc1", "desc2", ...]
}
PROMPT;
    }

    /**
     * Build PMAX generation prompt
     */
    protected function buildPMaxPrompt(AdvCampaign $campaign): string
    {
        return <<<PROMPT
Generate Google Ads Performance Max (PMAX) assets for the following campaign:

Campaign Name: {$campaign->name}
Campaign Briefing: {$campaign->info}
Keywords: {$campaign->keywords}
Destination URL: {$campaign->url}
Language: {$campaign->language}

Requirements:
- Generate 5 short titles (max 30 characters each)
- Generate 5 long titles (max 90 characters each)
- Generate 5 descriptions (max 90 characters each)
- Short titles: Punchy, concise, attention-grabbing
- Long titles: More descriptive, include benefits
- Descriptions: Compelling, include CTAs and value props
- Include keywords naturally
- Vary messaging to test different angles

Return JSON format:
{
  "short_titles": ["title1", "title2", ...],
  "long_titles": ["long1", "long2", ...],
  "descriptions": ["desc1", "desc2", ...]
}
PROMPT;
    }

    /**
     * Get system prompt based on campaign type and language
     */
    protected function getSystemPrompt(string $type, string $language): string
    {
        $langMap = [
            'it' => 'Italian',
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
        ];

        $languageName = $langMap[$language] ?? 'English';
        $typeUpper = strtoupper($type);

        return <<<SYSTEM
You are an expert Google Ads copywriter specializing in {$typeUpper} campaigns.

Your expertise includes:
- Writing compelling, conversion-focused ad copy
- Understanding Google Ads character limits and best practices
- Including keywords naturally without keyword stuffing
- Creating clear calls-to-action (CTAs)
- Testing multiple messaging angles
- Writing in {$languageName}

CRITICAL: Always return valid JSON format. Never exceed character limits.
SYSTEM;
    }

    /**
     * Validate titles array
     */
    protected function validateTitles(array $titles, int $maxChars, int $minCount, int $maxCount, string $type): array
    {
        if (count($titles) < $minCount || count($titles) > $maxCount) {
            throw new Exception("{$type} titles count must be between {$minCount} and {$maxCount}, got " . count($titles));
        }

        $validated = [];
        foreach ($titles as $title) {
            $title = trim($title);
            if (mb_strlen($title) > $maxChars) {
                // Truncate if too long
                $title = mb_substr($title, 0, $maxChars);
            }
            if (!empty($title)) {
                $validated[] = $title;
            }
        }

        if (count($validated) < $minCount) {
            throw new Exception("{$type} has too few valid titles after validation");
        }

        return array_slice($validated, 0, $maxCount);
    }

    /**
     * Validate descriptions array
     */
    protected function validateDescriptions(array $descriptions, int $maxChars, int $minCount, int $maxCount, string $type): array
    {
        if (count($descriptions) < $minCount || count($descriptions) > $maxCount) {
            throw new Exception("{$type} descriptions count must be between {$minCount} and {$maxCount}, got " . count($descriptions));
        }

        $validated = [];
        foreach ($descriptions as $desc) {
            $desc = trim($desc);
            if (mb_strlen($desc) > $maxChars) {
                // Truncate if too long
                $desc = mb_substr($desc, 0, $maxChars);
            }
            if (!empty($desc)) {
                $validated[] = $desc;
            }
        }

        if (count($validated) < $minCount) {
            throw new Exception("{$type} has too few valid descriptions after validation");
        }

        return array_slice($validated, 0, $maxCount);
    }

    /**
     * Calculate AI quality score (1-10)
     *
     * Factors:
     * - Keyword presence
     * - CTA presence
     * - Variety of messaging
     * - Character length utilization
     */
    protected function calculateQualityScore(array $assets, AdvCampaign $campaign): float
    {
        $score = 0;
        $maxScore = 100;

        // Extract keywords
        $keywords = $campaign->keywords_array;
        $allText = strtolower(json_encode($assets));

        // Factor 1: Keyword usage (30 points)
        $keywordScore = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($allText, strtolower($keyword))) {
                $keywordScore += 30 / count($keywords);
            }
        }
        $score += min(30, $keywordScore);

        // Factor 2: CTA presence (20 points)
        $ctas = ['buy', 'shop', 'get', 'try', 'learn', 'discover', 'start', 'join', 'sign up', 'order', 'claim', 'save'];
        $ctaFound = false;
        foreach ($ctas as $cta) {
            if (str_contains($allText, $cta)) {
                $ctaFound = true;
                break;
            }
        }
        if ($ctaFound) {
            $score += 20;
        }

        // Factor 3: Variety (30 points) - Check uniqueness
        $titles = $assets['titles'] ?? [];
        $descriptions = $assets['descriptions'] ?? [];
        $allItems = array_merge($titles, $descriptions);

        if (count($allItems) > 0) {
            $uniqueWords = [];
            foreach ($allItems as $item) {
                $words = explode(' ', strtolower($item));
                $uniqueWords = array_merge($uniqueWords, $words);
            }
            $uniqueRatio = count(array_unique($uniqueWords)) / max(1, count($uniqueWords));
            $score += $uniqueRatio * 30;
        }

        // Factor 4: Length utilization (20 points)
        $avgUtilization = 0;
        $count = 0;
        foreach ($titles as $title) {
            $avgUtilization += mb_strlen($title) / 30;
            $count++;
        }
        foreach ($descriptions as $desc) {
            $avgUtilization += mb_strlen($desc) / 90;
            $count++;
        }
        if ($count > 0) {
            $avgUtilization = $avgUtilization / $count;
            $score += $avgUtilization * 20;
        }

        // Normalize to 1-10 scale
        return round(($score / $maxScore) * 10, 2);
    }
}
