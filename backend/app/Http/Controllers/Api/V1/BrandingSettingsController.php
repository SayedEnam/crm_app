<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandingSettingsRequest;
use App\Models\ApplicationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingSettingsController extends Controller
{
    public function publicShow()
    {
        return response()->json(['data' => $this->payload()]);
    }

    public function show(Request $request)
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);

        return response()->json(['data' => $this->payload()]);
    }

    public function update(BrandingSettingsRequest $request)
    {
        $settings = ApplicationSetting::query()->firstOrCreate(['id' => 1]);
        $oldLogo = $settings->logo_path;
        $newLogo = null;

        if ($request->hasFile('logo')) {
            $newLogo = $request->file('logo')->store('branding', 'public');
            $settings->logo_path = $newLogo;
        } elseif ($request->boolean('remove_logo')) {
            $settings->logo_path = null;
        }

        $settings->app_name = $request->validated('app_name');
        $settings->company_name = $request->validated('company_name');

        try {
            $settings->save();
        } catch (\Throwable $exception) {
            if ($newLogo) {
                Storage::disk('public')->delete($newLogo);
            }

            throw $exception;
        }

        if ($oldLogo && $oldLogo !== $settings->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }

        return response()->json(['message' => 'Branding settings saved.', 'data' => $this->payload()]);
    }

    private function payload(): array
    {
        $settings = ApplicationSetting::query()->first();

        return [
            'app_name' => $settings?->app_name ?? 'ZomZom CRM',
            'company_name' => $settings?->company_name ?? 'ZomZom Travels & High Think HR Consultancy',
            'logo_url' => $settings?->logo_path ? url(Storage::disk('public')->url($settings->logo_path)) : null,
        ];
    }
}
