<?php

declare(strict_types=1);

namespace App\Http\Livewire\Organisations;

use App\Enums\OrganisationStatus;
use App\Http\Livewire\TableComponent;
use App\Repositories\Filters\OrganisationFilter;
use App\Repositories\OrganisationRepository;
use Illuminate\View\View;

class OrganisationList extends TableComponent
{
    public ?string $searchOrganisation = null;
    public ?string $status = null;
    public ?int $regionId = null;
    private OrganisationRepository $organisationRepository;

    public function boot(OrganisationRepository $repository)
    {
        $this->organisationRepository = $repository;
    }

    public function render(): View
    {
        $organisations = $this->organisationRepository->findByFilter(
            new OrganisationFilter([
                'search' => $this->searchOrganisation,
                'status' => $this->status ? OrganisationStatus::tryFrom($this->status) : null,
                'regionId' => $this->regionId,
                'sortBy' => $this->sort_by,
                'sortDirection' => $this->sort_direction,
                'relations' => ['region', 'curator'],
            ])
        );

        return view('livewire.organisations.organisation-list', [
            'organisations' => $organisations,
        ]);
    }

    protected function sortingOptions(): array
    {
        return [
            'name' => ['defaultDirection' => 'asc', 'isDefault' => true],
            'courses_count' => ['defaultDirection' => 'desc'],
        ];
    }

    protected function filterQueryString(): array
    {
        return [
            'searchOrganisation' => ['as' => 'search', 'except' => ''],
            'status' => ['as' => 'status', 'except' => ''],
            'regionId' => ['as' => 'region_id', 'except' => ''],
        ];
    }
}
