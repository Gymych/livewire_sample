@php use App\Enums\OrganisationStatus, App\Enums\Permission; @endphp
@section('page-title')
    Партнёры
@endsection

<div>
    <div class="flex justify-between items-start mb-8">
        <h1>@yield('page-title')</h1>
    </div>
    <div class="bg-white rounded-2xl">

        <x-filters count="{{ $this->getFilterCount() }}" :pagination="$organisations" item_names="партнёр, партнёра, партнёров">
            <div class="xl:col-span-4">
                <x-input wire:model.debounce.500ms="searchOrganisation" icon="search"
                         placeholder="Поиск партнёра"
                         autocomplete="off" autocorrect="off"
                />
            </div>
            <div class="xl:col-span-2">
                <x-select wire:model="status"
                          placeholder="Статус"
                          :options="OrganisationStatus::valuesWithTitles()"
                          option-label="title"
                          option-value="value"
                          autocomplete="off" autocorrect="off"
                />
            </div>

            <fieldset class="xl:col-span-2">
                <x-select wire:model="regionId"
                          placeholder="Регион"
                          :async-data="[
                            'api' => route('regions.search'),
                          ]"
                          option-label="name"
                          option-value="id"
                          option-description="-"
                          autocomplete="off" autocorrect="off"
                />
            </fieldset>
        </x-filters>

        <div class="overflow-x-auto border-b border-gray-100">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="text-gray-500 uppercase">
                <tr>
                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wider">
                        Название организации
                    </th>
                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wider">
                        ИНН
                    </th>
                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wider">
                        Город
                    </th>
                    <th scope="col"
                        class="px-4 py-3.5 text-left text-xs font-medium tracking-wider cursor-pointer"
                        wire:click="sortBy('courses_count')">
                        <div class="flex">
                            Количество программ
                            <x-sort field="courses_count"/>
                        </div>
                    </th>
                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wider">
                        Куратор
                    </th>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody wire:loading.delay.class="bg-slate-50" class="divide-y divide-gray-100">
                @forelse($organisations as $organisation)
                    <tr class="relative hover:bg-slate-50">
                        <td class="px-4 py-4 text-sm text-gray-500">
                            <a href="{{ route('organisations.show', $organisation->uuid) }}">{{ $organisation?->short_name }}</a>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $organisation?->inn }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $organisation?->city }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $organisation?->courses_count }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $organisation->curator ? $organisation->curator->full_name : 'Не назначен' }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                            @if($organisation->status !== OrganisationStatus::Approved)
                                <x-badge rounded color="{{ $organisation->status->color() }}"
                                         label="{{ $organisation->status->title() }}"/>
                            @endif
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                            <x-button href="{{ route('organisations.show', $organisation->uuid) }}" flat
                                      icon="pencil"></x-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center p-4">
                            @if($this->getFilterCount())
                                Не удалось найти партнеров по заданным критериям
                            @else
                                В системе пока нет партнеров
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pb-4">
            {{ $organisations->links() }}
        </div>
    </div>
</div>
