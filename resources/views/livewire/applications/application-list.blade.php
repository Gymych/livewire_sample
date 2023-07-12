@php use App\Enums\Permission; use \App\Enums\Role; @endphp
@section('page-title')
    Заявки на обучение
@endsection

<div>
    <div class="flex justify-between items-start mb-8">
        <h1>@yield('page-title')</h1>
    </div>

    <div @clear-applications.window="chosenApplications = []" x-data="{
        chosenApplications: [],
        courseId:@entangle('searchCourseRegionId').defer,
        applicationIds:@entangle('ids').defer,
        choseAll: false
    }" x-init="
        if (courseId) {
            Alpine.store('courseRegionId', courseId);
        }
        $watch('chosenApplications', function (val) { Alpine.store('chosenApplications', val); if (chosenApplications.length === 0) choseAll = false })
        $watch('courseId', function (val) { Alpine.store('courseRegionId', val); chosenApplications = []; choseAll = false })
    " class="bg-white rounded-2xl">
        <x-filters count="{{ $this->getFilterCount() }}" :pagination="$applications">
            <fieldset class="xl:col-span-4">
                <x-input
                        wire:model.debounce.400ms="search" icon="search"
                        placeholder="ФИО слушателя или номер заявки"
                        autocomplete="off" autocorrect="off"
                />
            </fieldset>

            @if(user()->hasRole([Role::Curator, Role::Admin]))
                <fieldset class="xl:col-span-2">
                    <x-select wire:model="searchOrganisationId"
                              placeholder="Партнер"
                              :async-data="route('organisations.search')"
                              option-label="short_name"
                              option-value="id"
                              option-description="-"
                              autocomplete="off" autocorrect="off"
                    />
                </fieldset>
            @endif

            <fieldset class="xl:col-span-2">
                <x-select wire:model="searchCourseId"
                          placeholder="Программа"
                          :async-data="[
                            'api' => route('courses.search'),
                            'params' => ['organisation_id' => user()?->organisation_id ?: $searchOrganisationId]
                          ]"
                          option-label="name"
                          option-value="id"
                          option-description="-"
                          autocomplete="off" autocorrect="off"
                />
            </fieldset>

            <fieldset class="xl:col-span-2">
                <x-select wire:model="searchCourseRegionId"
                          :placeholder="isset($searchCourseId) ? 'Место проведения' : 'Сначала выберите программу'"
                          :async-data="[
                            'api' => route('course-regions.search'),
                            'params' => ['course_id' => $searchCourseId]
                          ]"
                          option-label="label"
                          option-value="value"
                          option-description="description"
                          autocomplete="off" autocorrect="off"
                          :clearable="true"
                          :disabled="!isset($searchCourseId)"
                />
            </fieldset>

            <fieldset class="xl:col-span-2">
                <x-select
                        wire:model="searchStatus"
                        wire:key="searchStatus_{{$searchStopList}}"
                        id="searchStatus_{{rand(1, 1000)}}"
                        placeholder="Статус заявки"
                        :options="$statusOptions"
                        option-label="label"
                        option-value="value"
                        :disabled="$searchStopList"
                >
                </x-select>
            </fieldset>

            <fieldset class="xl:col-span-2">
                <x-select wire:model="searchYear" placeholder="Год" :options="['2023', '2022', '2021']"/>
            </fieldset>

            <x-slot name="actions">
                <div class="flex space-x-4 items-center">
                    @can(Permission::ApplicationsView->value)
                        <div {{ $waitingExportFile ? 'wire:poll' : '' }}>
                            @if(!$exportFile && !$waitingExportFile)
                                <x-button
                                        label="Выгрузить в Excel"
                                        sm class="w-full sm:w-auto"
                                        wire:click="exportToExcel"
                                />
                            @elseif(!$exportFile)
                                <x-button
                                        label="Готовим файл.."
                                        sm class="w-full sm:w-auto !cursor-wait"
                                        disabled
                                />
                            @else
                                <a href="{{ $exportFile }}">
                                    <x-button
                                            emerald
                                            label="Скачать выгрузку"
                                            sm class="w-full sm:w-auto"
                                            icon="document-download"
                                    />
                                </a>
                            @endif
                        </div>
                    @endcan
                    @can(Permission::ApplicationsApprove->value)
                        <x-toggle wire:loading.attr="disabled" wire:model="searchStopList"
                                  label="Стоп лист" lg></x-toggle>
                    @endcan
                    <x-toggle wire:loading.attr="disabled" wire:model="searchWithoutGroup"
                              label="Заявки без групп" lg></x-toggle>
                </div>
            </x-slot>
        </x-filters>
        @can(Permission::GroupsCreate->value)
            <div class="p-4">
                <x-message.info>Для добавления слушателей в группу установите фильтр "Заявки без групп"
                    и установите фильтр по образовательной программе
                </x-message.info>
                @if($searchWithoutGroup)
                    <div x-data="{createNewGroup:false}" x-show="courseId" x-cloak class="mt-4">
                        <div class="flex gap-4 mb-4">
                            <x-radio id="add_to_group" x-model="createNewGroup" :value="0"
                                     label="Добавление в существующую группу"/>
                            <x-radio id="create_new_group" x-model="createNewGroup" :value="1"
                                     label="Добавление в новую группу"/>
                            <div class="text-sm text-gray-500">
                                Заявок выбрано: <span class="text-gray-700 font-medium"
                                                      x-text="chosenApplications.length"></span>
                            </div>
                        </div>
                        <div x-show="createNewGroup==false">
                            @livewire('applications.applications-group-distribution')
                        </div>
                        <div x-show="createNewGroup==true" x-cloak>
                            @livewire('groups.group-create')
                        </div>
                    </div>
                @endif
            </div>
        @endcan
        <div class="overflow-x-auto border-b border-gray-100 mx-2">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="text-gray-500 uppercase">
                <tr class="">
                    @if($searchWithoutGroup)
                        <th scope="col" class="th-main text-gray-800">
                            <x-checkbox wire:key="checkbox_without_group"
                                        class="text-center cursor-pointer"
                                        x-model="choseAll"
                                        @click="chosenApplications = choseAll ? [] : applicationIds"
                            ></x-checkbox>
                        </th>
                    @endif
                    @if(user()->hasRole([Role::Curator, Role::Admin]))
                        <th scope="col" class="th-main">
                            Партнер
                        </th>
                    @endif
                    <th scope="col" class="th-main">
                        ФИО
                    </th>
                    <th scope="col" class="th-main cursor-pointer" wire:click="sortBy('external_created_at')">
                        <div class="flex">
                            Дата заявки
                            <x-sort field="external_created_at"/>
                        </div>
                    </th>
                    <th scope="col" class="th-main cursor-pointer" wire:click="sortBy('stage_end')">
                        <div class="flex">
                            Дедлайн
                            <x-sort field="stage_end"/>
                        </div>
                    </th>
                    <th scope="col" class="th-main">
                        Наименование программы
                    </th>
                    <th scope="col" class="th-main">
                        Категория гражданина
                    </th>
                    <th scope="col" class="th-main">
                        Статус заявки
                    </th>
                </tr>
                </thead>
                <tbody wire:loading.delay.class="bg-slate-50" class="divide-y divide-gray-100">
                @forelse($applications as $key => $application)
                    <tr class="relative hover:bg-slate-50">
                        @if($searchWithoutGroup)
                            <td x-data class="px-2 py-4 text-sm text-gray-500 font-medium text-right">
                                <x-checkbox
                                        class="text-center cursor-pointer"
                                        :value="$application->id"
                                        x-model="chosenApplications"
                                ></x-checkbox>
                            </td>
                        @endif
                        @if(user()->hasRole([Role::Curator, Role::Admin]))
                            <td class="px-2 py-4 text-sm text-gray-500">
                                {{ $application?->student?->user?->organisation?->short_name ?: $application?->courseRegion?->course?->organisation?->short_name }}
                            </td>
                        @endif
                        <td class="w-[9] px-2 py-4 text-sm text-gray-500 font-medium text-left">
                            <a href="{{ route('applications.view', ['uuid' => $application->uuid, 'tab' => 'application']) }}"
                               class="link">{{ $application?->name }}</a>
                            <p class="whitespace-nowrap font-normal"> {{ $application?->number }}</p>
                        </td>
                        <td class="px-2 py-4 text-sm text-gray-900">
                            {{ $application?->external_created_at->format('d.m.Y') }}
                        </td>
                        <td class="px-2 py-4 text-sm text-gray-500">
                            <div class="flex gap-2 {{$this->showDaysToDeadline($application) && $application->days_to_deadline < 6 ? 'text-rose-700' : ''}}">
                                {{ $application?->external_origin?->stage_end?->format('d.m.Y') ?: '-' }}
                                @if($this->showDaysToDeadline($application))
                                    <x-tooltip
                                            message="Необходимо направить договор слушателю до наступления даты дедлайна включительно.">
                                        <x-badge flat color="{{$this->deadlineColor($application->days_to_deadline)}}"
                                                 class="rounded-xl !text-xs !font-medium">
                                            {{ $application->days_to_deadline }}
                                        </x-badge>
                                    </x-tooltip>
                                @endif
                            </div>
                        </td>
                        <td class="px-2 py-4 text-sm text-gray-900">
                            <div class="flex items-center">
                                <div title="Отфильтровать по этой программе" class="self-start">
                                    <x-icon name="search"
                                            class="w-4 h-4 link cursor-pointer mr-2 mt-1 text-blue-200"
                                            wire:click="searchingCourseRegion({{$application?->courseRegion?->course_id}}, {{$application?->course_region_id}})"
                                    ></x-icon>
                                </div>
                                <div>
                                    {{ $application?->courseRegion?->course?->name }}
                                    <p class="text-gray-500">
                                        {{ $application?->courseRegion?->region?->name }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-2 py-4 text-sm text-gray-500">
                            {{ $application?->external_origin?->category->title() }}
                        </td>
                        <td class="px-2 py-4 text-sm text-gray-500">
                            {{ $application?->status->title() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center p-4">
                            @if($this->getFilterCount())
                                Не удалось найти заявки по заданным критериям
                            @else
                                В системе пока нет заявок на обучение
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pb-4">
            {{ $applications->links() }}
        </div>
    </div>
</div>
