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
            'language' => 'en', // Changed from 'it' to 'en'
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

    public function destroy($id)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $campaign = AdvCampaign::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        $campaign->delete();

        return redirect()->route('tenant.campaigns.index')
            ->with('success', 'Campaign deleted successfully');
    }

    private function generateMockAssets(AdvCampaign $campaign)
    {
        $assetType = $campaign->type === 'rsa' ? 'headline' : 'description';
        $count = $campaign->type === 'rsa' ? 5 : 3;

        $mockContent = [
            'headline' => [
                'Professional SEO Services | Boost Your Ranking',
                'Expert Web Design | Custom Solutions',
                'Digital Marketing Agency | Results Driven',
                'SEO Optimization | Increase Traffic Today',
                'Affordable Web Development | Quality First',
            ],
            'description' => [
                'Transform your online presence with our comprehensive digital marketing solutions. Expert team, proven results.',
                'Get more customers with professional SEO services. Custom strategies tailored to your business goals.',
                'Professional web design and development. Modern, responsive websites that convert visitors into customers.',
            ],
        ];

        for ($i = 0; $i < $count; $i++) {
            AdvGeneratedAsset::create([
                'campaign_id' => $campaign->id,
                'asset_type' => $assetType,
                'content' => $mockContent[$assetType][$i] ?? "Sample {$assetType} " . ($i + 1),
                'tokens_used' => rand(50, 150),
            ]);
        }

        // Update campaign tokens
        $campaign->tokens_used = $campaign->assets->sum('tokens_used');
        $campaign->save();
    }
}
