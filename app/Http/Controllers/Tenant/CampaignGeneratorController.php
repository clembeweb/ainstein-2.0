<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdvCampaign;
use App\Models\AdvGeneratedAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignGeneratorController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $query = AdvCampaign::where('tenant_id', $tenant->id);

        // Filters
        if ($request->filled('campaign_type')) {
            $query->where('type', strtolower($request->get('campaign_type')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $campaigns = $query->withCount('assets')->orderBy('created_at', 'desc')->paginate(20);

        // For filters
        $campaignTypes = ['RSA', 'PMAX'];
        $statuses = ['draft', 'completed', 'failed'];

        return view('tenant.campaigns.index', compact('campaigns', 'campaignTypes', 'statuses'));
    }

    public function create()
    {
        return view('tenant.campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_name' => 'required|string|max:255',
            'campaign_type' => 'required|in:RSA,PMAX',
            'business_description' => 'required|string',
            'target_keywords' => 'required|string',
        ]);

        $user = Auth::user();
        $tenant = $user->tenant;

        $campaign = AdvCampaign::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['campaign_name'],
            'type' => strtolower($validated['campaign_type']),
            'info' => $validated['business_description'],
            'keywords' => $validated['target_keywords'],
            'language' => 'it', // Italian language for campaigns
            'url' => $request->input('url', 'https://example.com'),
        ]);

        // Generate real AI assets using CampaignAssetsGenerator service
        try {
            $service = app(\App\Services\Tools\CampaignAssetsGenerator::class);
            $asset = $service->generate($campaign);

            return redirect()->route('tenant.campaigns.show', $campaign->id)
                ->with('success', 'Campaign created successfully! AI generated ' . count($asset->titles ?? []) . ' assets.');
        } catch (\Exception $e) {
            // If AI generation fails, still redirect but show error
            return redirect()->route('tenant.campaigns.show', $campaign->id)
                ->with('warning', 'Campaign created but AI generation failed: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $campaign = AdvCampaign::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->with('assets')
            ->firstOrFail();

        return view('tenant.campaigns.show', compact('campaign'));
    }

    public function edit($id)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $campaign = AdvCampaign::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        // Check authorization
        $this->authorize('update', $campaign);

        return view('tenant.campaigns.edit', compact('campaign'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $campaign = AdvCampaign::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        // Check authorization
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'campaign_name' => 'required|string|max:255',
            'business_description' => 'required|string',
            'target_keywords' => 'required|string',
            'url' => 'nullable|url|max:500',
            'language' => 'nullable|string|in:it,en,es,fr,de',
        ]);

        $campaign->update([
            'name' => $validated['campaign_name'],
            'info' => $validated['business_description'],
            'keywords' => $validated['target_keywords'],
            'url' => $validated['url'] ?? $campaign->url,
            'language' => $validated['language'] ?? $campaign->language,
        ]);

        return redirect()->route('tenant.campaigns.show', $campaign->id)
            ->with('success', 'Campaign aggiornata con successo!');
    }

    public function regenerate($id)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $campaign = AdvCampaign::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        // Check authorization (uses regenerate policy which checks tokens)
        $this->authorize('regenerate', $campaign);

        try {
            // Delete old assets
            $campaign->assets()->delete();

            // Generate new assets using CampaignAssetsGenerator service
            $service = app(\App\Services\Tools\CampaignAssetsGenerator::class);
            $asset = $service->generate($campaign);

            return redirect()->route('tenant.campaigns.show', $campaign->id)
                ->with('success', 'Assets rigenerati con successo! Creati ' . count($asset->titles ?? []) . ' nuovi asset.');
        } catch (\Exception $e) {
            \Log::error('Campaign regeneration failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('tenant.campaigns.show', $campaign->id)
                ->with('error', 'Errore durante la rigenerazione: ' . $e->getMessage());
        }
    }

    public function export($id, $format = 'csv')
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $campaign = AdvCampaign::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->with('assets')
            ->firstOrFail();

        // Check authorization
        $this->authorize('export', $campaign);

        if (!$campaign->assets || $campaign->assets->isEmpty()) {
            return redirect()->route('tenant.campaigns.show', $campaign->id)
                ->with('warning', 'Nessun asset da esportare per questa campaign.');
        }

        $asset = $campaign->assets->first();

        if ($format === 'csv') {
            return $this->exportCSV($campaign, $asset);
        } elseif ($format === 'google-ads') {
            return $this->exportGoogleAds($campaign, $asset);
        }

        return redirect()->route('tenant.campaigns.show', $campaign->id)
            ->with('error', 'Formato export non valido.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $campaign = AdvCampaign::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        // Check authorization
        $this->authorize('delete', $campaign);

        $campaignName = $campaign->name;
        $campaign->delete();

        return redirect()->route('tenant.campaigns.index')
            ->with('success', "Campaign \"{$campaignName}\" eliminata con successo.");
    }

    /**
     * Export campaign assets as CSV
     */
    private function exportCSV(AdvCampaign $campaign, AdvGeneratedAsset $asset)
    {
        $filename = 'campaign_' . $campaign->id . '_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($campaign, $asset) {
            $file = fopen('php://output', 'w');

            // Header row
            if ($campaign->type === 'rsa') {
                fputcsv($file, ['Type', 'Content', 'Character Count']);

                // Titles
                foreach ($asset->titles ?? [] as $title) {
                    fputcsv($file, ['Title', $title, mb_strlen($title)]);
                }

                // Descriptions
                foreach ($asset->descriptions ?? [] as $desc) {
                    fputcsv($file, ['Description', $desc, mb_strlen($desc)]);
                }
            } else { // PMAX
                fputcsv($file, ['Type', 'Content', 'Character Count']);

                // Short titles
                foreach ($asset->titles ?? [] as $title) {
                    fputcsv($file, ['Short Title', $title, mb_strlen($title)]);
                }

                // Long titles
                foreach ($asset->long_titles ?? [] as $title) {
                    fputcsv($file, ['Long Title', $title, mb_strlen($title)]);
                }

                // Descriptions
                foreach ($asset->descriptions ?? [] as $desc) {
                    fputcsv($file, ['Description', $desc, mb_strlen($desc)]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export campaign assets in Google Ads compatible format
     */
    private function exportGoogleAds(AdvCampaign $campaign, AdvGeneratedAsset $asset)
    {
        $filename = 'google_ads_' . $campaign->id . '_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($campaign, $asset) {
            $file = fopen('php://output', 'w');

            if ($campaign->type === 'rsa') {
                // Google Ads RSA format
                fputcsv($file, ['Campaign', 'Ad Group', 'Headline 1', 'Headline 2', 'Headline 3',
                                'Headline 4', 'Headline 5', 'Headline 6', 'Headline 7', 'Headline 8',
                                'Headline 9', 'Headline 10', 'Headline 11', 'Headline 12', 'Headline 13',
                                'Headline 14', 'Headline 15', 'Description 1', 'Description 2',
                                'Description 3', 'Description 4', 'Final URL']);

                $row = [
                    $campaign->name,
                    'Ad Group 1',
                ];

                // Add up to 15 headlines
                $titles = $asset->titles ?? [];
                for ($i = 0; $i < 15; $i++) {
                    $row[] = $titles[$i] ?? '';
                }

                // Add up to 4 descriptions
                $descriptions = $asset->descriptions ?? [];
                for ($i = 0; $i < 4; $i++) {
                    $row[] = $descriptions[$i] ?? '';
                }

                $row[] = $campaign->url ?? '';

                fputcsv($file, $row);
            } else { // PMAX
                // Google Ads Performance Max format
                fputcsv($file, ['Campaign', 'Asset Group', 'Short Headline 1', 'Short Headline 2',
                                'Short Headline 3', 'Short Headline 4', 'Short Headline 5',
                                'Long Headline 1', 'Long Headline 2', 'Long Headline 3',
                                'Long Headline 4', 'Long Headline 5',
                                'Description 1', 'Description 2', 'Description 3',
                                'Description 4', 'Description 5', 'Final URL']);

                $row = [
                    $campaign->name,
                    'Asset Group 1',
                ];

                // Add short headlines (up to 5)
                $titles = $asset->titles ?? [];
                for ($i = 0; $i < 5; $i++) {
                    $row[] = $titles[$i] ?? '';
                }

                // Add long headlines (up to 5)
                $longTitles = $asset->long_titles ?? [];
                for ($i = 0; $i < 5; $i++) {
                    $row[] = $longTitles[$i] ?? '';
                }

                // Add descriptions (up to 5)
                $descriptions = $asset->descriptions ?? [];
                for ($i = 0; $i < 5; $i++) {
                    $row[] = $descriptions[$i] ?? '';
                }

                $row[] = $campaign->url ?? '';

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
