<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class MockOpenAiService
{
    private $model;

    public function __construct()
    {
        $this->model = config('openai.model', 'gpt-3.5-turbo');
    }

    /**
     * Generate mock content for demo purposes
     */
    public function generateContent(string $prompt, array $variables = [], string $model = null): array
    {
        try {
            Log::info("MockOpenAiService: Generating demo content", [
                'prompt' => $prompt,
                'variables' => $variables
            ]);

            // Process prompt variables
            $processedPrompt = $this->processPromptVariables($prompt, $variables);

            // Generate mock content based on prompt context
            $mockContent = $this->generateMockContentByContext($processedPrompt, $variables);

            // Generate mock meta title and description
            $keyword = $variables['keyword'] ?? $variables['target_keyword'] ?? 'contenuto SEO';
            $metaTitle = $this->generateMockMetaTitle($keyword);
            $metaDescription = $this->generateMockMetaDescription($keyword);

            return [
                'content' => $mockContent,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'tokens_used' => $this->estimateTokens($mockContent),
                'model_used' => $model ?? $this->model,
                'is_mock' => true
            ];

        } catch (Exception $e) {
            Log::error('MockOpenAiService content generation failed: ' . $e->getMessage());
            throw new Exception('Mock content generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Simple content generation for background jobs
     */
    public function generateSimpleContent(string $prompt): string
    {
        try {
            $mockResponses = [
                "Questo è un contenuto demo generato automaticamente per testare la piattaforma Ainstein. In una configurazione di produzione, questo testo sarebbe generato dall'intelligenza artificiale di OpenAI utilizzando il prompt fornito. Il contenuto sarebbe ottimizzato per SEO e personalizzato in base alle tue esigenze specifiche.",

                "Contenuto di esempio per dimostrare le capacità della piattaforma Ainstein. Questo testo rappresenta quello che l'AI genererebbe in base al tuo prompt. Il sistema supporta la generazione di articoli, meta description, titoli SEO e molto altro, tutto ottimizzato per i motori di ricerca.",

                "Demo content generato dalla piattaforma Ainstein. Questo è un esempio di come l'intelligenza artificiale creerebbe contenuti personalizzati basati sui tuoi template e variabili. Il sistema reale utilizzerebbe OpenAI per produrre contenuti unici e di alta qualità per ogni richiesta."
            ];

            return $mockResponses[array_rand($mockResponses)];

        } catch (Exception $e) {
            Log::error('MockOpenAiService simple content generation failed: ' . $e->getMessage());
            return "Contenuto demo non disponibile. Errore: " . $e->getMessage();
        }
    }

    /**
     * Generate context-aware mock content
     */
    private function generateMockContentByContext(string $prompt, array $variables): string
    {
        $keyword = $variables['keyword'] ?? $variables['target_keyword'] ?? 'argomento specifico';
        $context = strtolower($prompt);

        // Check for campaign generation requests
        if (str_contains($context, 'rsa') || str_contains($context, 'responsive search ads')) {
            return $this->generateRSACampaignMock($variables);
        }

        if (str_contains($context, 'pmax') || str_contains($context, 'performance max')) {
            return $this->generatePMAXCampaignMock($variables);
        }

        if (str_contains($context, 'articolo') || str_contains($context, 'blog')) {
            return $this->generateBlogArticleMock($keyword);
        }

        if (str_contains($context, 'meta description')) {
            return $this->generateMetaDescriptionMock($keyword);
        }

        if (str_contains($context, 'titolo') || str_contains($context, 'h1')) {
            return $this->generateTitleMock($keyword);
        }

        if (str_contains($context, 'prodotto') || str_contains($context, 'ecommerce')) {
            return $this->generateProductDescriptionMock($keyword, $variables);
        }

        return $this->generateGenericMock($keyword);
    }

    private function generateBlogArticleMock(string $keyword): string
    {
        return "# Guida Completa: {$keyword}

## Introduzione

Nel panorama digitale di oggi, comprendere appieno {$keyword} è diventato essenziale per il successo online. Questa guida ti fornirà tutte le informazioni necessarie per padroneggiare questo argomento.

## Cosa Devi Sapere su {$keyword}

{$keyword} rappresenta un elemento cruciale per:
- Migliorare le performance del tuo business
- Ottimizzare la strategia digitale
- Aumentare l'engagement con il tuo pubblico

## Strategie Efficaci

### 1. Pianificazione
Una strategia ben pianificata per {$keyword} include:
- Analisi del mercato di riferimento
- Definizione degli obiettivi
- Identificazione del target

### 2. Implementazione
L'implementazione di {$keyword} richiede:
- Risorse adeguate
- Monitoraggio costante
- Ottimizzazione continua

## Best Practices

Per ottenere i migliori risultati con {$keyword}, segui queste best practices:
- Mantieni sempre l'utente al centro
- Utilizza dati per guidare le decisioni
- Testa e ottimizza regolarmente

## Conclusione

{$keyword} è un investimento strategico che può trasformare il tuo business. Implementando le strategie discusse in questa guida, potrai ottenere risultati significativi e duraturi.

*Nota: Questo è contenuto demo generato dalla piattaforma Ainstein per scopi di testing.*";
    }

    private function generateMetaDescriptionMock(string $keyword): string
    {
        $descriptions = [
            "Scopri tutto su {$keyword} con la nostra guida completa. Strategie, consigli e best practices per il successo.",
            "Guida definitiva a {$keyword}: tecniche avanzate e consigli pratici per ottenere risultati eccellenti.",
            "Tutto quello che devi sapere su {$keyword}. Approfondimenti, strategie e soluzioni innovative.",
        ];

        return $descriptions[array_rand($descriptions)];
    }

    private function generateTitleMock(string $keyword): string
    {
        $titles = [
            "Guida Completa a {$keyword}: Strategie e Best Practices",
            "{$keyword}: Come Ottenere Risultati Eccellenti",
            "Padroneggia {$keyword} con Questa Guida Definitiva",
            "Tutto su {$keyword}: Consigli e Strategie Vincenti",
            "{$keyword} Spiegato: Dalla Teoria alla Pratica"
        ];

        return $titles[array_rand($titles)];
    }

    private function generateProductDescriptionMock(string $productName, array $variables): string
    {
        $features = $variables['features'] ?? 'caratteristiche avanzate';

        return "## {$productName}

Scopri {$productName}, la soluzione innovativa che ti offre {$features} e prestazioni eccezionali.

### Caratteristiche Principali:
{$features}

### Benefici per Te:
- Qualità superiore garantita
- Facilità d'uso straordinaria
- Supporto clienti dedicato
- Garanzia di soddisfazione

### Perché Scegliere {$productName}

{$productName} è stato progettato per soddisfare le tue esigenze specifiche, offrendo una combinazione unica di funzionalità avanzate e semplicità d'uso.

**Ordina ora e scopri la differenza!**

*Demo content generato da Ainstein Platform*";
    }

    private function generateGenericMock(string $keyword): string
    {
        return "Contenuto professionale generato per: {$keyword}

Questo è un esempio di contenuto che verrebbe creato dall'intelligenza artificiale di Ainstein. Il sistema analizza il tuo prompt e le variabili fornite per generare contenuti unici, coinvolgenti e ottimizzati per SEO.

In una configurazione di produzione, questo contenuto sarebbe:
- Completamente personalizzato in base alle tue esigenze
- Ottimizzato per i motori di ricerca
- Creato utilizzando le tecnologie AI più avanzate
- Pronto per la pubblicazione

La piattaforma Ainstein supporta la generazione di vari tipi di contenuto:
- Articoli blog
- Descrizioni prodotti
- Meta title e description
- Contenuti per social media
- E molto altro ancora

*Questo è contenuto demo per scopi di testing della piattaforma.*";
    }

    /**
     * Generate mock RSA campaign assets
     */
    private function generateRSACampaignMock(array $variables): string
    {
        $businessInfo = $variables['business_info'] ?? 'business innovativo';
        $keywords = $variables['keywords'] ?? 'qualità, innovazione, eccellenza';

        $titles = [
            "Soluzioni Premium di Qualità",
            "Offerte Esclusive per Te",
            "Innovazione al Tuo Servizio",
            "Qualità Garantita al 100%",
            "Promozione Speciale Limitata",
            "Esperienza Unica Garantita",
            "Servizio Clienti Eccellente",
            "Risparmia Oggi - Offerta Spec",
            "Leader nel Settore dal 2024",
            "Consegna Rapida Garantita",
            "Prezzi Imbattibili Online",
            "Soddisfazione Garantita",
            "Prodotti di Alta Qualità",
            "Offerta Esclusiva Online",
            "Scopri le Nostre Novità"
        ];

        $descriptions = [
            "Scopri la nostra gamma completa di prodotti e servizi di alta qualità. Offerte esclusive e promozioni speciali.",
            "Qualità superiore e prezzi competitivi. Servizio clienti dedicato e consegna rapida in tutta Italia.",
            "Leader nel settore con anni di esperienza. Soluzioni innovative per ogni esigenza. Contattaci ora!",
            "Garanzia di soddisfazione al 100%. Prodotti certificati e servizio post-vendita eccellente."
        ];

        $response = [
            'titles' => $titles,
            'descriptions' => $descriptions
        ];

        return json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Generate mock PMAX campaign assets
     */
    private function generatePMAXCampaignMock(array $variables): string
    {
        $businessInfo = $variables['business_info'] ?? 'azienda leader';
        $keywords = $variables['keywords'] ?? 'innovazione, tecnologia, futuro';

        $shortTitles = [
            "Qualità Premium Garantita",
            "Offerte Esclusive Online",
            "Innovazione e Tecnologia",
            "Servizio Clienti Top",
            "Promozioni Imperdibili"
        ];

        $longTitles = [
            "Scopri la Nostra Gamma Completa di Prodotti e Servizi di Alta Qualità",
            "Soluzioni Innovative per Ogni Esigenza - Qualità e Convenienza Garantite",
            "Leader nel Settore con Anni di Esperienza - Affidabilità e Professionalità",
            "Offerte Esclusive e Promozioni Speciali - Risparmia Oggi con Noi",
            "Tecnologia Avanzata e Servizio Clienti Eccellente - La Tua Scelta Migliore"
        ];

        $descriptions = [
            "Scopri la qualità superiore dei nostri prodotti e servizi. Offerte esclusive, consegna rapida e assistenza dedicata.",
            "Leader nel settore con soluzioni innovative per ogni esigenza. Qualità certificata e prezzi competitivi garantiti.",
            "Esperienza e professionalità al tuo servizio. Prodotti di alta qualità e servizio clienti eccellente. Contattaci ora!",
            "Promozioni speciali e offerte limitate. Garanzia di soddisfazione e assistenza post-vendita dedicata. Ordina oggi!",
            "Innovazione e tecnologia avanzata per risultati superiori. Affidabilità garantita e supporto completo sempre."
        ];

        $response = [
            'titles' => $shortTitles,
            'long_titles' => $longTitles,
            'descriptions' => $descriptions
        ];

        return json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function generateMockMetaTitle(string $keyword): string
    {
        $templates = [
            "{$keyword}: Guida Completa 2024 | Ainstein",
            "Tutto su {$keyword} - Strategie e Consigli | Ainstein",
            "{$keyword} Spiegato: Best Practices | Ainstein",
            "Padroneggia {$keyword} con Ainstein Platform",
        ];

        return $templates[array_rand($templates)];
    }

    private function generateMockMetaDescription(string $keyword): string
    {
        $templates = [
            "Scopri {$keyword} con Ainstein. Guida completa, strategie efficaci e consigli pratici per il successo.",
            "Tutto su {$keyword}: tecniche avanzate, best practices e soluzioni innovative con Ainstein Platform.",
            "Padroneggia {$keyword} facilmente. Contenuti di qualità e strategie vincenti con Ainstein.",
        ];

        return $templates[array_rand($templates)];
    }

    /**
     * Process variables in prompt template
     */
    private function processPromptVariables(string $prompt, array $variables): string
    {
        $processedPrompt = $prompt;

        foreach ($variables as $key => $value) {
            $processedPrompt = str_replace("{{" . $key . "}}", $value, $processedPrompt);
        }

        return $processedPrompt;
    }

    /**
     * Estimate tokens used (mock calculation)
     */
    private function estimateTokens(string $content): int
    {
        return ceil(str_word_count($content) * 1.3); // Rough estimation: ~1.3 tokens per word
    }
}