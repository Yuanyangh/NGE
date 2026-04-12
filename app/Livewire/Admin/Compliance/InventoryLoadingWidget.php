<?php

namespace App\Livewire\Admin\Compliance;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Services\Compliance\InventoryLoadingDetector;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class InventoryLoadingWidget extends Component
{
    #[Locked]
    public int $companyId;
    public string $scanDate;

    // Inline editing
    public string $threshold = '0.80';
    public bool $thresholdSaved = false;

    public function mount(int $companyId, string $scanDate): void
    {
        $this->companyId = $companyId;
        $this->scanDate  = $scanDate;
        $this->threshold = CompanySetting::getValue($companyId, 'inventory_loading_threshold', '0.80');
    }

    public function saveThreshold(): void
    {
        $this->validate([
            'threshold' => ['required', 'numeric', 'between:0.01,0.99'],
        ]);

        CompanySetting::withoutGlobalScopes()
            ->updateOrCreate(
                ['company_id' => $this->companyId, 'key' => 'inventory_loading_threshold'],
                ['value' => number_format((float) $this->threshold, 2)],
            );

        $this->thresholdSaved = true;
    }

    #[Computed]
    public function flaggedAffiliates(): Collection
    {
        $detector = app(InventoryLoadingDetector::class);

        return $detector->scan(
            companyId: $this->companyId,
            date: Carbon::parse($this->scanDate),
        );
    }

    #[Computed]
    public function company(): Company
    {
        return Company::findOrFail($this->companyId);
    }

    public function render()
    {
        return view('livewire.admin.compliance.inventory-loading-widget');
    }
}
