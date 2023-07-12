<?php

declare(strict_types=1);

namespace App\Http\Livewire\Applications;

use App\Enums\IAS\ApplicationStatusEnum;
use App\Http\Livewire\Groups\GroupCreate;
use App\Http\Livewire\TableComponent;
use App\Http\Livewire\Traits\WithDaysToDeadline;
use App\Jobs\ExportApplicationsJob;
use App\Repositories\ApplicationRepository;
use App\Repositories\CourseRepository;
use App\Repositories\Filters\ApplicationFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ApplicationsList extends TableComponent
{
    use WithDaysToDeadline;

    public ?string $search = null;
    public ?int $searchCourseId = null;
    public ?int $searchCourseRegionId = null;
    public ?int $searchOrganisationId = null;
    public ?bool $searchWithoutGroup = false;
    public ?bool $searchStopList = false;
    public ?string $searchYear = null;
    public ?string $searchStatus = null;
    public array $statusOptions = [];
    public array $ids = [];
    public bool $waitingExportFile = false;
    public ?string $exportFile;

    public $listeners = [
        GroupCreate::GROUP_CREATED_EVENT => '$refresh',
        ApplicationsGroupDistribution::GROUP_FILLED_EVENT => '$refresh',
    ];

    private ApplicationRepository $applicationRepository;
    private CourseRepository $courseRepository;

    public function boot(
        ApplicationRepository          $applicationRepository,
        CourseRepository               $courseRepository,
    ): void {
        $this->applicationRepository = $applicationRepository;
        $this->courseRepository = $courseRepository;
    }

    public function mount(): void
    {
        $this->statusOptions = collect(ApplicationStatusEnum::cases())
            ->map(fn ($s) => ['value' => $s->value, 'label' => $s->title()])
            ->toArray();
    }

    public function updatingSearchStopList($value): void
    {
        $this->searchStatus = $value ? ApplicationStatusEnum::ProcessingByOperator->value : null;
    }

    public function updatingSearchCourseId($value): void
    {
        $this->searchCourseRegionId = $value
            ? $this->courseRepository->find($value)?->courseRegions->first()?->id
            : null;
    }

    public function updatingSearchOrganisationId(): void
    {
        $this->searchCourseId = null;
        $this->searchCourseRegionId = null;
    }

    public function searchingCourseRegion(int $courseId, int $searchCourseRegionId): void
    {
        $this->searchCourseId = $courseId;
        $this->searchCourseRegionId = $searchCourseRegionId;
    }

    public function render(): View
    {
        $applications = $this->applicationRepository->getFilteredWithCourse($this->makeFilterData());
        $this->ids = $applications->pluck('id')->toArray();
        $this->searchOrganisationId = $this->searchCourseId ? $this->courseRepository->find($this->searchCourseId)->organisation_id : null;
        $this->setExportFile();

        return view('livewire.applications.application-list', [
            'applications' => $applications,
        ]);
    }

    public function exportToExcel(): void
    {
        $this->waitingExportFile = true;

        ExportApplicationsJob::dispatch(user()->id, $this->makeFilterData());
    }

    protected function sortingOptions(): array
    {
        return [
            'stage_end' => ['defaultDirection' => 'desc'],
            'external_created_at' => ['defaultDirection' => 'desc', 'isDefault' => true],
        ];
    }

    protected function filterQueryString(): array
    {
        return [
            'search' => ['except' => '', 'as' => 'search'],
            'searchYear' => ['except' => '', 'as' => 'year'],
            'searchStatus' => ['except' => '', 'as' => 'status'],
            'searchCourseId' => ['except' => '', 'as' => 'course'],
            'searchCourseRegionId' => ['except' => '', 'as' => 'place'],
            'searchOrganisationId' => ['except' => '', 'as' => 'organisation'],
            'searchStopList' => ['except' => false, 'as' => 'stop_list'],
            'searchWithoutGroup' => ['except' => false, 'as' => 'without_group'],
        ];
    }

    private function makeFilterData(): ApplicationFilter
    {
        return ApplicationFilter::make([
            'search' => nullTrim($this->search),
            'courseRegionId' => $this->searchCourseRegionId,
            'courseId' => $this->searchCourseId,
            'withoutGroup' => $this->searchWithoutGroup,
            'year' => $this->searchYear,
            'status' => $this->searchStatus,
            'partnerId' => Auth::user()?->organisation_id ?: $this->searchOrganisationId,
            'sortBy' => $this->sort_by,
            'sortDirection' => $this->sort_direction,
            'stopList' => $this->searchStopList,
        ]);
    }

    private function setExportFile(): void
    {
        $export = user()
            ->loadMissing('exports')
            ->exports()
            ->where('created_at', '>', now()->subMinutes(config('dna.exports.file_lifetime'))->toDateTimeString())
            ->first();

        $this->exportFile = $export ? Storage::disk('exports')->url($export->filename) : null;
    }
}
